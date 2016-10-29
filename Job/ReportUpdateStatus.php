<?php
/*
 * Copyright 2016 CampaignChain, Inc. <info@campaignchain.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CampaignChain\Operation\TwitterBundle\Job;

use CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityFact;
use CampaignChain\CoreBundle\Entity\SchedulerReportOperation;
use CampaignChain\CoreBundle\Job\JobReportInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ReportUpdateStatus implements JobReportInterface
{
    const BUNDLE_NAME = 'campaignchain/operation-twitter';
    const METRIC_RTS = 'Retweets';
    const METRIC_FAVS = 'Favorites';

    protected $em;
    protected $container;

    protected $message;

    protected $status;

    public function __construct(ManagerRegistry $managerRegistry, ContainerInterface $container)
    {
        $this->em = $managerRegistry->getManager();
        $this->container = $container;
    }

    public function schedule($operation, $facts = null)
    {
        $scheduler = new SchedulerReportOperation();
        $scheduler->setOperation($operation);
        $scheduler->setInterval('1 hour');
        $scheduler->setEndAction($operation->getActivity()->getCampaign());
        $this->em->persist($scheduler);

        // Add initial data to report.
        $this->status = $this->em->getRepository('CampaignChainOperationTwitterBundle:Status')->findOneByOperation($operation);
        if (!$this->status) {
            throw new \Exception('No status message found for an operation with ID: '.$operation->getId());
        }

        $facts[self::METRIC_RTS] = 0;
        $facts[self::METRIC_FAVS] = 0;

        $factService = $this->container->get('campaignchain.core.fact');
        $factService->addFacts('activity', self::BUNDLE_NAME, $operation, $facts);
    }

    public function execute($operationId)
    {
        $this->status = $this->em->getRepository('CampaignChainOperationTwitterBundle:Status')->findOneByOperation($operationId);
        if (!$this->status) {
            throw new \Exception('No status message found for an operation with ID: '.$operationId);
        }

        $client = $this->container->get('campaignchain.channel.twitter.rest.client');
        $connection = $client->connectByActivity($this->status->getOperation()->getActivity());

        $request = $connection->get('statuses/retweets/'.$this->status->getIdStr().'.json?count=1&trim_user=true');
        $response = $request->send()->json();

        // If response is an empty array, this means no interaction happened yet
        // with the Tweet.
        if(!count($response)){
            $retweets = 0;
            $favorites = 0;
        } else {
            $retweets = $response[0]['retweeted_status']['retweet_count'];
            $favorites = $response[0]['retweeted_status']['favorite_count'];
        }

        // Add report data.
        $facts[self::METRIC_RTS] = $retweets;
        $facts[self::METRIC_FAVS] = $favorites;

        $factService = $this->container->get('campaignchain.core.fact');
        $factService->addFacts('activity', self::BUNDLE_NAME, $this->status->getOperation(), $facts);

        $this->message = 'Added to report: retweets = '.$retweets.', favorites = '.$favorites.'';

        return self::STATUS_OK;
    }

    public function getMessage(){
        return $this->message;
    }
}