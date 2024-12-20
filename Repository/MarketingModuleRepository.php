<?php

namespace Webkul\UVDesk\SupportCenterBundle\Repository;

use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\SupportCenterBundle\Entity\MarketingModule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Doctrine\ORM\Tools\Pagination\Paginator;

class MarketingModuleRepository extends ServiceEntityRepository
{
    public $safeFields = array('page','limit','sort','order','direction');
    const LIMIT = 10;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarketingModule::class);
    }
   
    public function getAllMarketingModules(\Symfony\Component\HttpFoundation\ParameterBag $obj = null, $container)
    {
        $json = array();
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('a')->from($this->getEntityName(), 'a');

        $data = $obj->all();
        $data = array_reverse($data);
       
        foreach ($data as $key => $value) {
            if (! in_array($key, $this->safeFields)) {
                if ($key != 'dateUpdated' AND $key != 'dateAdded' AND $key != 'search') {
                    $qb->andWhere('a.'.$key.' = :'.$key);
                    $qb->setParameter($key, $value);
                } else {
                    if ($key == 'search') {
                        $qb->orWhere('a.title'.' LIKE :name');
                        $qb->setParameter('name', '%'.urldecode(trim($value)).'%');
                        $qb->orWhere('a.description'.' LIKE :description');
                        $qb->setParameter('description', '%'.urldecode(trim($value)).'%');
                    }
                }
            }
        }

        if (! isset($data['sort'])){
            $qb->orderBy('a.id',Criteria::DESC);
        }

        $paginator  = $container->get('knp_paginator');

        $results = $paginator->paginate(
            $qb,
            isset($data['page']) ? $data['page'] : 1,
            self::LIMIT,
            array('distinct' => false)
        );

        $newResult = [];
        foreach ($results as $key => $result) {
            $newResult[] = array(
                'id'           => $result->getId(),
                'title'        => $result->getTitle(),
                'description'  => $result->getDescription(),
                'isActive'     => $result->getIsActive(),
                'createdAt'    => $result->getCreatedAt(),
                'updatedAt'    => $result->getUpdatedAt(),
                'group'        => $result->getGroup()->getId() == 1 ? $group = ['name' => 'Default Group'] : $group = ['name' => $result->getGroup()->getName()],
            );
        }

        $paginationData = $results->getPaginationData();
        $queryParameters = $results->getParams();

        $paginationData['url'] = '#'.$container->get('uvdesk.service')->buildPaginationQuery($queryParameters);

        $json['groups'] = $newResult;
        $json['pagination_data'] = $paginationData;

        return $json;
    }

    public function getAllMarketingModulesForCustomer($query, $container, $customer)
    {
        $order = array_rand(array(
            'DESC' => 'DESC',
            'ASC'  => 'ASC'
        ));
    
        $column = array_rand(array(
            'mm.id'        => 'mm.id',
            'mm.createdAt' => 'mm.createdAt'
        ));

        $ticket = $this->getEntityManager()->getRepository(Ticket::class)->findOneById($query->get('ticketId'));

        $qb = $this->getEntityManager()->createQueryBuilder();
        $entityClass = MarketingModule::class;
        $limit = (int)$query->get('limit');

        $qb->select('mm')
            ->from($entityClass, 'mm')
            ->join(Ticket::class, 't', 'WITH', 'mm.group = t.supportGroup')
            ->where('mm.isActive = :isActive')
            ->andWhere('t.customer = :userId')
            ->andWhere('mm.group = :groupId')
            ->groupBy('mm.id')
            ->orderBy($column, $order)
            ->setParameter('isActive', 1)
            ->setParameter('groupId', $ticket->getSupportGroup()->getId())
            ->setParameter('userId', $customer)
            ->setMaxResults($limit);

        $paginator  = $container->get('knp_paginator');
        $results = $paginator->paginate(
            $qb,
            $query->get('page'),
            $limit,
            array('distinct' => false)
        );

        $newResult = [];
        foreach ($results as $key => $result) {
            $newResult[] = array(
                'id'           => $result->getId(),
                'title'        => $result->getTitle(),
                'description'  => $result->getDescription(),
                'isActive'     => $result->getIsActive(),
                'linkURL'      => $result->getLinkUrl(),
                'image'        => $result->getImage(),
                'borderColor'  => $result->getBorderColor(),
                'createdAt'    => $result->getCreatedAt(),
                'updatedAt'    => $result->getUpdatedAt(),
                'group'        => $result->getGroup()->getId() == 1 ? $group = ['name' => 'Default Group'] : $group = ['name' => $result->getGroup()->getName()],
            );
        }

        $paginationData = $results->getPaginationData();

        $json['modules'] = ($newResult);
        $json['pagination_data'] = $paginationData;
        
        return $json;
    }
}
