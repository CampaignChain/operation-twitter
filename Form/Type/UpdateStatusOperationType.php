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

namespace CampaignChain\Operation\TwitterBundle\Form\Type;

use CampaignChain\AutocompleteFormTypeBundle\Form\Type\AutocompleteType;
use CampaignChain\CoreBundle\Form\Type\OperationType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UpdateStatusOperationType extends OperationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('message', AutocompleteType::class, array(
                'property_path' => 'message',
                'label' => false,
                'attr' => array(
                    'placeholder' => 'Compose message...',
                    'maxlength_soft' => 140
                ),
                'campaignchain_autocomplete' => array(
                    '@' => array(
                        'endpoint' => '/api/private/p/campaignchain/channel-twitter/users/search',
                        'location' => $this->location->getId(),
                    )
                )
            ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $defaults = array(
            'data_class' => 'CampaignChain\Operation\TwitterBundle\Entity\Status',
        );

        if($this->content){
            $defaults['data'] = $this->content;
        }
        $resolver->setDefaults($defaults);
    }

    public function getBlockPrefix()
    {
        return 'campaignchain_operation_twitter_update_status';
    }
}