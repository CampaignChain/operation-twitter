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

namespace CampaignChain\Operation\TwitterBundle\Entity;

use CampaignChain\CoreBundle\Entity\Meta;
use Doctrine\ORM\Mapping as ORM;
use CampaignChain\CoreBundle\Util\ParserUtil;

/**
 * @ORM\Entity
 * @ORM\Table(name="campaignchain_operation_twitter_status")
 */
class Status extends Meta
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="CampaignChain\CoreBundle\Entity\Operation", cascade={"persist"})
     */
    protected $operation;

    /**
     * @ORM\Column(type="text")
     */
    protected $message;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $idStr;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $url;

    /**
     * The raw data of the published tweet as stored by Twitter upon
     * publication.
     *
     * @ORM\Column(type="array", nullable=true)
     */
    protected $twitterData;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Status
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set idStr
     *
     * @param string $idStr
     * @return Status
     */
    public function setIdStr($idStr)
    {
        $this->idStr = $idStr;

        return $this;
    }

    /**
     * Get idStr
     *
     * @return string 
     */
    public function getIdStr()
    {
        return $this->idStr;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Status
     */
    public function setUrl($url)
    {
        $this->url = ParserUtil::sanitizeUrl($url);

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set twitterData
     *
     * @param string $twitterData
     * @return Status
     */
    public function setLinkedinData($twitterData)
    {
        $this->twitterData = $twitterData;

        return $this;
    }

    /**
     * Get twitterData
     *
     * @return string
     */
    public function getLinkedinData()
    {
        return $this->twitterData;
    }

    /**
     * Set operation
     *
     * @param \CampaignChain\CoreBundle\Entity\Operation $operation
     * @return Status
     */
    public function setOperation(\CampaignChain\CoreBundle\Entity\Operation $operation = null)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Get operation
     *
     * @return \CampaignChain\CoreBundle\Entity\Operation
     */
    public function getOperation()
    {
        return $this->operation;
    }
}
