<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Operation\TwitterBundle\EntityService;

use Doctrine\ORM\EntityManager;
use CampaignChain\CoreBundle\EntityService\OperationServiceInterface;
use CampaignChain\CoreBundle\Entity\Operation;

class Status implements OperationServiceInterface
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getStatusByOperation($id){
        $status = $this->em->getRepository('CampaignChainOperationTwitterBundle:Status')
            ->findOneByOperation($id);

        if (!$status) {
            throw new \Exception(
                'No status found by operation id '.$id
            );
        }

        return $status;
    }

    public function cloneOperation(Operation $oldOperation, Operation $newOperation)
    {
        $status = $this->getStatusByOperation($oldOperation);
        $clonedStatus = clone $status;
        $clonedStatus->setOperation($newOperation);
        $this->em->persist($clonedStatus);
        $this->em->flush();
    }

    public function removeOperation($id){
        try {
            $operation = $this->getStatusByOperation($id);
            $this->em->remove($operation);
            $this->em->flush();
        } catch (\Exception $e) {

        }
    }
}