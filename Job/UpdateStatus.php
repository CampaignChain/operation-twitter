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

use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\Operation\TwitterBundle\Entity\Status;
use Doctrine\ORM\EntityManager;
use CampaignChain\CoreBundle\Entity\Medium;
use CampaignChain\CoreBundle\Job\JobActionInterface;
use Guzzle\Http\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateStatus implements JobActionInterface
{
    protected $em;
    protected $container;

    protected $message;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function execute($operationId)
    {
        /** @var Status $status */
        $status = $this->em->getRepository('CampaignChainOperationTwitterBundle:Status')->findOneByOperation($operationId);

        if (!$status) {
            throw new \Exception('No status message found for an operation with ID: '.$operationId);
        }

        // Process URLs in message and save the new message text, now including
        // the replaced URLs with the Tracking ID attached for call to action tracking.
        $ctaService = $this->container->get('campaignchain.core.cta');
        $status->setMessage(
            $ctaService->processCTAs($status->getMessage(), $status->getOperation(), 'txt')->getContent()
        );

        $client = $this->container->get('campaignchain.channel.twitter.rest.client');
        /** @var Client $connection */
        $connection = $client->connectByActivity($status->getOperation()->getActivity());

        $params['status'] = $status->getMessage();

        //have images?
        $images = $this->em
            ->getRepository('CampaignChainHookImageBundle:Image')
            ->getImagesForOperation($status->getOperation());

        $mediaIds = [];

        if ($images) {
            foreach ($images as $image) {
                $streamPath = 'gaufrette://images/'.$image->getPath();

                $imageRequest = $connection->post('https://upload.twitter.com/1.1/media/upload.json', null, [
                    'media_data' => base64_encode(file_get_contents($streamPath)),
                ]);

                try {
                    $response = $imageRequest->send()->json();
                    $mediaIds[] = $response['media_id'];
                } catch (\Exception $e) {
                }
            }

            if ($mediaIds) {
                $params['media_ids'] = implode(',', $mediaIds);
            }
        }

        /*
         * @TODO
         *
         * If there are URLs in the tweet, they have been shortened. Thus, we'll
         * pass the expanded URLs as entities in the API call, so that Twitter
         * can display them when hovering the mouse on a short URL.
         */

        $request = $connection->post('statuses/update.json', null, $params);
        $response = $request->send()->json();

        // TODO
        // If status code is 403, this means that the same tweet with identical content already exists
        // This should be checked upon creation of tweet (same with FB!)

        // Set URL to published status message on Facebook
        $statusURL = 'https://twitter.com/'.$response['user']['screen_name'].'/status/'.$response['id_str'];

        $status->setUrl($statusURL);
        $status->setIdStr($response['id_str']);
        // Set Operation to closed.
        $status->getOperation()->setStatus(Action::STATUS_CLOSED);

        $location = $status->getOperation()->getLocations()[0];
        $location->setIdentifier($response['id_str']);
        $location->setUrl($statusURL);
        $location->setName($status->getOperation()->getName());
        $location->setStatus(Medium::STATUS_ACTIVE);

        // Schedule data collection for report
        $report = $this->container->get('campaignchain.job.report.twitter.update_status');
        $report->schedule($status->getOperation());

        $this->em->flush();

        $this->message = 'The message "'.$response['text'].'" with the ID "'.$response['id_str'].'" has been posted on Twitter. See it on Twitter: <a href="'.$statusURL.'">'.$statusURL.'</a>';

        return self::STATUS_OK;
//            }
//            else {
//                // Handle errors, if authentication did not work.
//                // 1) Check if App is installed.
//                // 2) check if access token is valid and retrieve new access token if necessary.
//                // Log error, send email, prompt user, ask to check App Key and Secret or to authenticate again
//            }
    }

    public function getMessage(){
        return $this->message;
    }
}