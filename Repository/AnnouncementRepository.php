<?php

namespace Webkul\UVDesk\SupportCenterBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket;
use Webkul\UVDesk\SupportCenterBundle\Entity\Announcement;

/**
 * @method Announcement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Announcement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Announcement[]    findAll()
 * @method Announcement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnnouncementRepository extends ServiceEntityRepository
{
    public $safeFields = array('page', 'limit', 'sort', 'order', 'direction');
    const LIMIT = 10;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Announcement::class);
    }

    public function getAllAnnouncements(\Symfony\Component\HttpFoundation\ParameterBag $obj = null, $container)
    {
        $json = array();
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('a')->from($this->getEntityName(), 'a');

        $data = $obj->all();
        $data = array_reverse($data);

        foreach ($data as $key => $value) {
            if (! in_array($key, $this->safeFields)) {
                if ($key != 'dateUpdated' and $key != 'dateAdded' and $key != 'search') {
                    $qb->andWhere('a.' . $key . ' = :' . $key);
                    $qb->setParameter($key, $value);
                } else {
                    if ($key == 'search') {
                        $qb->orWhere('a.title' . ' LIKE :name');
                        $qb->setParameter('name', '%' . urldecode($value) . '%');
                        $qb->orWhere('a.promoText' . ' LIKE :promoText');
                        $qb->setParameter('promoText', '%' . urldecode($value) . '%');
                    }
                }
            }
        }

        if (! isset($data['sort'])) {
            $qb->orderBy('a.id', Criteria::DESC);
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
                'id'        => $result->getId(),
                'title'     => $result->getTitle(),
                'promoText' => $result->getPromoText(),
                'promoTag'  => $result->getPromoTag(),
                'tagColor'  => $result->getTagColor(),
                'linkText'  => $result->getLinkText(),
                'linkUrl'   => $result->getLinkUrl(),
                'isActive'  => $result->getIsActive(),
                'createdAt' => $result->getCreatedAt(),
                'group'     => array(
                    'id'    => $result->getGroup()->getId(),
                    'name'  => $result->getGroup()->getName()
                )
            );
        }

        $paginationData = $results->getPaginationData();
        $queryParameters = $results->getParams();

        $paginationData['url'] = '#' . $container->get('uvdesk.service')->buildPaginationQuery($queryParameters);

        $json['groups'] = $newResult;
        $json['pagination_data'] = $paginationData;

        return $json;
    }

    public function getAllAnnouncementForCustomer($query, $container, $customer)
    {
        $order = array_rand(array(
            'DESC' => 'DESC',
            'ASC'  => 'ASC'
        ));

        $column = array_rand(array(
            'ma.id'        => 'ma.id',
            'ma.createdAt' => 'ma.createdAt'
        ));

        $qb = $this->getEntityManager()->createQueryBuilder();
        $entityClass = Announcement::class;
        $limit = 10;

        $qb->select('ma')
            ->from($entityClass, 'ma')
            ->join(Ticket::class, 't', 'WITH', 'ma.group = t.supportGroup')
            ->where('ma.isActive = :isActive')
            ->andWhere('t.customer = :userId')
            ->groupBy('ma.id')
            ->orderBy($column, $order)
            ->setParameter('isActive', 1)
            ->setParameter('userId', $customer)
            ->setMaxResults($limit);

        $paginator  = $container->get('knp_paginator');
        $results = $paginator->paginate(
            $qb,
            $query->get('page') ?: 1,
            $limit,
            array('distinct' => false)
        );

        $newResult = [];

        foreach ($results as $key => $result) {
            $newResult[] = array(
                'id'        => $result->getId(),
                'title'     => $result->getTitle(),
                'promoText' => $result->getPromoText(),
                'promoTag'  => $result->getPromoTag(),
                'isActive'  => $result->getIsActive(),
                'linkURL'   => $result->getLinkUrl(),
                'linkText'  => $result->getLinkText(),
                'createdAt' => $result->getCreatedAt(),
                'updatedAt' => $result->getUpdatedAt(),
                'group'     => $result->getGroup()->getId() == 1 ? $group = ['name' => 'Default Group'] : $group = ['name' => $result->getGroup()->getName()],
            );
        }

        $paginationData = $results->getPaginationData();

        $json['modules'] = ($newResult);
        $json['pagination_data'] = $paginationData;

        return $json;
    }
}
