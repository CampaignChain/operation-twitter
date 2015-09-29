<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Operation\TwitterBundle\Form\Type;

use CampaignChain\CoreBundle\Form\Type\OperationType;
use Symfony\Component\Form\FormBuilderInterface;

class UpdateStatusOperationType extends OperationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('message', 'textarea', array(
                'property_path' => 'message',
                'label' => false,
                'attr' => array(
                    'placeholder' => 'Compose message...',
                    'maxlength' => 140,
                ),
            ));
    }

    public function getName()
    {
        return 'campaignchain_operation_twitter_update_status';
    }
}