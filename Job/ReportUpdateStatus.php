<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Operation\TwitterBundle\Job;

use CampaignChain\CoreBundle\Entity\ReportAnalyticsActivityFact;
use CampaignChain\CoreBundle\Entity\SchedulerReportOperation;
use CampaignChain\CoreBundle\Job\JobReportInterface;
use Doctrine\ORM\EntityManager;

class ReportUpdateStatus implements JobReportInterface
{
    const METRIC_NAME_RT = 'Retweets';
    const METRIC_NAME_FAV = 'Favorites';

    protected $em;
    protected $container;

    protected $message;

    protected $status;

    public function __construct(EntityManager $em, $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function schedule($operation)
    {
        $scheduler = new SchedulerReportOperation();
        $scheduler->setOperation($operation);
        $scheduler->setInterval('1 hour');
        $scheduler->setEndAction($operation->getActivity()->getCampaign());
        $this->em->persist($scheduler);

        // Add initial data to report.
        $this->status = $this->em->getRepository('CampaignChainOperationTwitterBundle:Status')->findOneByOperation($operation);
        $this->setReportData(0, 0);
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
        $this->setReportData($retweets, $favorites);

        $this->message = 'Added to report: retweets = '.$retweets.', favorites = '.$favorites.'';

        return self::STATUS_OK;
    }

    public function getMessage(){
        return $this->message;
    }

    public function setReportData($retweets, $favorites)
    {
        // Get metrics object.
        $metricRt = $this->em->getRepository('CampaignChainCoreBundle:ReportAnalyticsActivityMetric')->findOneByName(self::METRIC_NAME_RT);

        // Create new facts entry for retweets.
        $factRt = new ReportAnalyticsActivityFact();
        $factRt->setMetric($metricRt);
        $factRt->setOperation($this->status->getOperation());
        $factRt->setActivity($this->status->getOperation()->getActivity());
        $factRt->setCampaign($this->status->getOperation()->getActivity()->getCampaign());
        $factRt->setTime(new \DateTime('now', new \DateTimeZone('UTC')));
        $factRt->setValue($retweets);
        $this->em->persist($factRt);

        // Get metrics object.
        $metricFav = $this->em->getRepository('CampaignChainCoreBundle:ReportAnalyticsActivityMetric')->findOneByName(self::METRIC_NAME_FAV);

        // Create new facts entry for favorites.
        $factFav = new ReportAnalyticsActivityFact();
        $factFav->setMetric($metricFav);
        $factFav->setOperation($this->status->getOperation());
        $factFav->setActivity($this->status->getOperation()->getActivity());
        $factFav->setCampaign($this->status->getOperation()->getActivity()->getCampaign());
        $factFav->setTime(new \DateTime('now', new \DateTimeZone('UTC')));
        $factFav->setValue($favorites);
        $this->em->persist($factFav);
    }
}