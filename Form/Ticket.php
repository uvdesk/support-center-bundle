<?php

namespace Webkul\UVDesk\SupportCenterBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;

class Ticket extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $container = $options['container'];

        if (
            ! is_object($container->get('user.service')->getSessionUser())
            || $container->get('user.service')->getSessionUser() == 'anon.'
        ) {

            $builder->add('name', null, [
                'required' => true,
                'label'    => 'Your Name',
                'attr'     => array('placeholder' => 'Enter Your Name'),
                'mapped'   => false,
            ]);

            $builder->add('from', EmailType::class, array(
                'required' => true,
                'label'    => 'Your Email',
                'mapped'   => false,
                'attr'     => array('placeholder' => 'Enter Your Email'),
            ));
        }

        $builder->add('type', EntityType::class, array(
            'class'        => CoreEntities\TicketType::class,
            'choice_label' => 'description',
            'multiple'     => false,
            'mapped'       => false,
            'attr' => array(
                'data-role'        => 'tagsinput',
                'data-live-search' => true,
                'class'            => 'selectpicker'
            ),
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('type')
                    ->andWhere('type.isActive = :isActive')
                    ->setParameter('isActive', true)
                    ->orderBy('type.description', 'ASC');
            },
            'placeholder' => 'Choose query type',
            'empty_data'  => null
        ));

        $builder->add('subject', null, array(
            'required' => true,
            'label'    => 'Subject',
            'mapped'   => false,
            'attr'     => ['placeholder' => 'Enter Subject'],
        ));

        $builder->add('reply', TextareaType::class, array(
            'label'  => 'Message',
            'mapped' => false,
            'attr' => array(
                'placeholder'      => 'Brief Description about your query',
                'data-iconlibrary' => "fa",
                'data-height'      => "250",
            ),
        ));

        $builder->add('attachments', FileType::class, array(
            'label' => '+ Attach File',
            'mapped' => false,
            'multiple' => true,
            'attr' => array(
                'mainLabel'          => false,
                'infoLabel'          => 'right',
                'infoLabelText'      => '+ Attach File',
                'decorateFile'       => true,
                'decorateCss'        => 'attach-file',
                'enableRemoveOption' => true
            ),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'         => CoreEntities\Ticket::class,
            'allow_extra_fields' => true,
            'csrf_protection'    => false
        ));

        $resolver->setRequired('container');
        $resolver->setRequired('entity_manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return '';
    }
}
