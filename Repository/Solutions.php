<?php

namespace Webkul\UVDesk\SupportCenterBundle\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;
use Webkul\UVDesk\SupportCenterBundle\Entity as SupportEntities;

class Solutions extends \Doctrine\ORM\EntityRepository
{
    const LIMIT = 10;

    private $defaultImage = '';
    private $defaultSort = 'a.id';
    private $direction = ['asc', 'desc'];
    private $sorting = ['a.name', 'a.dateAdded'];
    private $safeFields = ['page', 'limit', 'sort', 'order', 'direction'];
    private $allowedFormFields = ['search', 'name', 'description', 'visibility'];

    private function validateSorting($sorting)
    {
        return in_array($sorting, $this->sorting) ? $sorting : $this->defaultSort;
    }

    private function validateDirection($direction)
    {
        return in_array($direction, $this->direction) ? $direction : Criteria::DESC;
    }

    private function presetting(&$data)
    {
        $data['sort'] = $_GET['sort'] = $this->validateSorting(isset($data['sort']) ? $data['sort'] : false);
        $data['direction'] = $_GET['direction'] = $this->validateDirection(isset($data['direction']) ? $data['direction'] : false);

        $this->cleanAllData($data);
    }

    private function cleanAllData(&$data)
    {
        if (isset($data['isActive'])) {
            $data['visibility'] = ($data['isActive'] ? 'public' : 'private');
            unset($data['isActive']);
        }
    }

    public function getAllCategories($categoryLimit = null, $articleLimit = null)
    {
        $categoryResponse = [];
        $categoryQB = $this->getEntityManager()->createQueryBuilder()->select('sc.id, sc.name, sc.description')
            ->from(SupportEntities\SolutionCategory::class, 'sc')
            ->andWhere('sc.status = :status')->setParameter('status', true)
            ->orderBy('sc.dateAdded', 'DESC');            
        
        return $categoryQB->getQuery()->getResult();
    }

    public function getAllSolutions(\Symfony\Component\HttpFoundation\ParameterBag $obj = null, $container, $allResult = false, $status = [0, 1])
    {
        $json = array();
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('a')->from($this->getEntityName(), 'a');

        $data = $obj ? $obj->all() : [];
        $data = array_reverse($data);

        $this->presetting($data);
        foreach ($data as $key => $value) {
            if (
                ! in_array($key,$this->safeFields) 
                && in_array($key, $this->allowedFormFields)
            ) {
                if ($key !='dateUpdated' AND $key != 'dateAdded' AND $key != 'search') {
                    $qb->andWhere('a.'.$key.' = :'.$key);
                    $qb->setParameter($key, $value);
                } else {
                    if ($key == 'search') {
                        $qb->orWhere('a.name'.' LIKE :name');
                        $qb->setParameter('name', '%'.urldecode(trim($value)).'%');
                        $qb->orWhere('a.description'.' LIKE :description');
                        $qb->setParameter('description', '%'.urldecode(trim($value)).'%');
                    }
                }
            }
        }

        if (! $allResult) {
            $paginator  = $container->get('knp_paginator');

            $results = $paginator->paginate(
                $qb,
                isset($data['page']) ? $data['page'] : 1,
                self::LIMIT,
                array('distinct' => false)
            );
        } else {
            $qb->select($allResult);
            $results = $qb->getQuery()->getResult();

            return $results;
        }
      
        $newResult = [];
        foreach ($results as $key => $result) {
            $newResult[] = array(
                'id'              => $result->getId(),
                'name'            => $result->getName(),
                'description'     => $result->getDescription(),
                'visibility'      => $result->getVisibility(),
                'solutionImage'   => ($result->getSolutionImage() == null) ? $this->defaultImage : $result->getSolutionImage(),
                'categoriesCount' => $this->getCategoriesCountBySolution($result->getId(), $status),
                'categories'      => $this->getCategoriesWithCountBySolution($result->getId(), $status),
                'articleCount'    => $this->getArticlesCountBySolution($result->getId(), $status)
            );
        }

        $paginationData = $results->getPaginationData();
        $queryParameters = $results->getParams();

        $paginationData['url'] = '#'.$container->get('uvdesk.service')->buildPaginationQuery($queryParameters);

        $json['results'] = $newResult;
        $json['pagination_data'] = $paginationData;
       
        return $json;
    }

    public function findSolutionById($filterArray = [])
    {
        $json = array();
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('a')->from($this->getEntityName(), 'a');

        foreach ($filterArray as $key => $value) {
            $qb->andWhere('a.'.$key.' = :'.$key);
            $qb->setParameter($key, $value);
        }

        $result = $qb->getQuery()->getOneOrNullResult();
        
        return($result);
    }

    public function getCategoriesWithCountBySolution($id, $status = [1])
    {
        $queryBuilder = $this->createQueryBuilder('a');

        $categories = $queryBuilder
            ->select('sc.id, sc.name')
            ->leftJoin('Webkul\UVDesk\SupportCenterBundle\Entity\SolutionCategoryMapping','ac','WITH', 'ac.solutionId = a.id')
            ->leftJoin('Webkul\UVDesk\SupportCenterBundle\Entity\SolutionCategory','sc','WITH', 'ac.categoryId = sc.id')
            ->andWhere('ac.solutionId = :solutionId')
            ->andWhere('sc.status IN (:status)')
            ->setParameters([
                'solutionId' => $id,
                'status' => $status,
            ])
            ->orderBy('sc.sortOrder', Criteria::ASC)
            ->getQuery()
            ->getResult()
        ;

        if ($categories) {
            $solutionCategoryRepository = $this->getEntityManager()->getRepository(SupportEntities\SolutionCategory::class);
            
            foreach ($categories as $key => $category) {
                $categories[$key]['articleCount'] = $solutionCategoryRepository->getArticlesCountByCategory($category['id']);
            }
        }
        
        return $categories;
    }

    public function getCategoriesCountBySolution($id, $status = [1])
    {
        $queryBuilder = $this->createQueryBuilder('a');

        $result = $queryBuilder
            ->select('COUNT(a.id)')
            ->leftJoin('Webkul\UVDesk\SupportCenterBundle\Entity\SolutionCategoryMapping','ac','WITH', 'ac.solutionId = a.id')
            ->leftJoin('Webkul\UVDesk\SupportCenterBundle\Entity\SolutionCategory','sc','WITH', 'ac.categoryId = sc.id')
            ->andWhere('ac.solutionId = :solutionId')
            ->andWhere('sc.status IN (:status)')
            ->setParameters([
                'solutionId' => $id ,
                'status' => $status,
            ])
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $result;
    }

    public function getArticlesCountBySolution($id, $status = [1])
    {
        $queryBuilder = $this->createQueryBuilder('a');
        $result = $queryBuilder
            ->select('COUNT(DISTINCT aa.id)')
            ->leftJoin('Webkul\UVDesk\SupportCenterBundle\Entity\SolutionCategoryMapping','sac','WITH', 'sac.solutionId = a.id')
            ->leftJoin('Webkul\UVDesk\SupportCenterBundle\Entity\ArticleCategory','ac','WITH', 'sac.categoryId = ac.categoryId')
            ->leftJoin('Webkul\UVDesk\SupportCenterBundle\Entity\Article','aa','WITH', 'ac.articleId = aa.id')
            ->where('sac.solutionId = :solutionId')
            ->andWhere('ac.id IS NOT NULL')
            ->andWhere('aa.status != :status')
            ->setParameters([
                'solutionId' => $id,
                'status' => 0
            ])
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $result;
    }
    
    public function removeEntryBySolution($id)
    {
        $where = is_array($id) ? 'ac.solutionId IN (:id)' : 'ac.solutionId = :id';

        $queryBuilder = $this->createQueryBuilder('ac');
        $queryBuilder
            ->delete(SupportEntities\SolutionCategoryMapping::class, 'ac')
            ->andWhere($where)
            ->setParameters([
                'id' => $id ,
            ])
            ->getQuery()
            ->execute()
        ;
    }
}