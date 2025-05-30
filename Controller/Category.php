<?php

namespace Webkul\UVDesk\SupportCenterBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\SupportCenterBundle\Entity as SupportEntities;

class Category extends AbstractController
{
    const LIMIT = 10;

    private $userService;
    private $translator;
    private $entityManager;

    public function __construct(UserService $userService, TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        $this->userService = $userService;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
    }

    public function CategoryList(Request $request, ContainerInterface $container)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $solutions = $this->entityManager
            ->getRepository(SupportEntities\Solutions::class)
            ->getAllSolutions(null, $container, 'a.id, a.name');

        $solutions = array_map(function ($solution) {
            return [
                'id'              => $solution['id'],
                'name'            =>  $solution['name'],
                'categoriesCount' => $this->entityManager
                    ->getRepository(SupportEntities\Solutions::class)
                    ->getCategoriesCountBySolution($solution['id'])
            ];
        }, $solutions);

        return $this->render('@UVDeskSupportCenter/Staff/Category/categoryList.html.twig', [
            'solutions' => $solutions
        ]);
    }

    public function CategoryListBySolution(Request $request)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $solution = $this->entityManager
            ->getRepository(SupportEntities\Solutions::class)
            ->findSolutionById(['id' => $request->attributes->get('solution')]);
        if ($solution) {
            $solution_category = [
                'solution'              => $solution,
                'solutionArticleCount'  => $this->entityManager
                    ->getRepository(SupportEntities\Solutions::class)
                    ->getArticlesCountBySolution($request->attributes->get('solution')),
                'solutionCategoryCount' => $this->entityManager
                    ->getRepository(SupportEntities\Solutions::class)
                    ->getCategoriesCountBySolution($request->attributes->get('solution')),
            ];
            return $this->render('@UVDeskSupportCenter/Staff/Category/categoryListBySolution.html.twig', $solution_category);
        } else
            $this->noResultFound();
    }

    public function CategoryListXhr(Request $request, ContainerInterface $container)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $json = array();
        $repository = $this->entityManager->getRepository(SupportEntities\SolutionCategory::class);

        if ($request->attributes->get('solution'))
            $request->query->set('solutionId', $request->attributes->get('solution'));
        else
            $request->query->set('limit', self::LIMIT);
        $json =  $repository->getAllCategories($request->query, $container);
        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function Category(Request $request, ContainerInterface $container)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        if ($request->attributes->get('id')) {
            $category = $this->entityManager->getRepository(SupportEntities\SolutionCategory::class)->findOneById($request->attributes->get('id'));

            if (! $category) {
                $this->noResultFound();
            }
        } else {
            $category = new SupportEntities\SolutionCategory;
        }

        $categorySolutions = [];
        if ($category->getId())
            $categorySolutions = $this->entityManager
                ->getRepository(SupportEntities\SolutionCategory::class)
                ->getSolutionsByCategory($category->getId());

        $errors = [];
        if ($request->getMethod() == "POST") {
            $data = $request->request->all();

            $category->setName($data['name']);
            $category->setDescription($data['description']);
            $category->setSortOrder($data['sortOrder']);
            $category->setDateAdded($category->getDateAdded() ?? new \DateTime());
            $category->setDateUpdated(new \DateTime());
            $category->setSorting($data['sorting']);
            $category->setStatus($data['status']);

            $this->entityManager->persist($category);
            $this->entityManager->flush();

            $tempSolutions = explode(',', $request->request->get('tempSolutions'));

            $oldSolutions = [];

            if ($categorySolutions) {
                foreach ($categorySolutions as $solution) {
                    if ($key = array_search($solution['id'], $tempSolutions))
                        unset($tempSolutions[$key]);
                    else
                        $oldSolutions[] = $solution['id'];
                }

                if ($oldSolutions) {
                    $this->entityManager
                        ->getRepository(SupportEntities\SolutionCategory::class)
                        ->removeSolutionsByCategory($category->getId(), $oldSolutions);
                }
            }

            if ($tempSolutions) {
                foreach ($tempSolutions as $solution) {
                    if ($solution) {
                        $solutionCategoryMapping = new SupportEntities\SolutionCategoryMapping();
                        $solutionCategoryMapping->setSolutionId($solution);
                        $solutionCategoryMapping->setCategoryId($category->getId());
                        $this->entityManager->persist($solutionCategoryMapping);
                    }
                }
            }

            $this->entityManager->flush();
            $message = $this->translator->trans('Success! Category has been added successfully.');

            $this->addFlash('success', $message);

            return $this->redirect($this->generateUrl('helpdesk_member_knowledgebase_category_collection'));
        }

        $solutions = $this->entityManager
            ->getRepository(SupportEntities\Solutions::class)
            ->getAllSolutions(null, $container, 'a.id, a.name');

        return $this->render('@UVDeskSupportCenter/Staff/Category/categoryForm.html.twig', [
            'category'          => $category,
            'categorySolutions' => $categorySolutions,
            'solutions'         => $solutions,
            'errors'            => json_encode($errors)
        ]);
    }

    public function CategoryXhr(Request $request, ContainerInterface $container)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $json = array();

        if ($request->getMethod() == "POST") {
            $data = $request->request->get("data");
            $dataIds = array_map('intval', $data['ids']);

            switch ($data['actionType']) {
                case 'sortUpdate':
                    foreach ($dataIds as $sort => $id) {
                        $this->entityManager->getRepository(SupportEntities\SolutionCategory::class)->categorySortingUpdate($id, $sort);
                    }
                    $json['alertClass'] = 'success';
                    $json['alertMessage'] = $this->translator->trans('Success ! Category sort  order updated successfully.');
                    break;
                case 'status':
                    $this->entityManager->getRepository(SupportEntities\SolutionCategory::class)->bulkCategoryStatusUpdate($dataIds, $data['targetId']);
                    $json['alertClass'] = 'success';
                    $json['alertMessage'] = $this->translator->trans('Success ! Category status updated successfully.');
                    break;
                case 'solutionUpdate':
                    if ($data['action'] == 'remove') {
                        $this->entityManager
                            ->getRepository(SupportEntities\SolutionCategory::class)
                            ->removeSolutionsByCategory($data['ids'][0], [$data['solutionId']]);
                    } elseif ($data['action'] == 'add') {
                        $solutionCategoryMapping = new SupportEntities\SolutionCategoryMapping();
                        $solutionCategoryMapping->setSolutionId($data['solutionId']);
                        $solutionCategoryMapping->setCategoryId($data['ids'][0]);
                        $this->entityManager->persist($solutionCategoryMapping);
                        $this->entityManager->flush();
                    }

                    $json['alertClass'] = 'success';
                    $json['alertMessage'] = $this->translator->trans('Success ! Folders updated successfully.');

                    break;
                case 'delete':
                    if ($dataIds) {
                        foreach ($dataIds as $id) {
                            $category = $this->entityManager->getRepository(SupportEntities\SolutionCategory::class)->find($id);

                            if ($category) {
                                $this->entityManager->remove($category);
                                $this->entityManager->flush();
                            }
                        }

                        $this->removeCategory($dataIds);

                        $json['alertClass'] = 'success';
                        $json['alertMessage'] = $this->translator->trans('Success ! Categories removed successfully.');
                    }
                    break;
            }
        } elseif ($request->getMethod() == "PUT") {
            $content = json_decode($request->getContent(), true);
            $id = $content['id'];
            $category = $this->entityManager->getRepository(SupportEntities\SolutionCategory::class)->find($id);

            if ($category) {
                $form = $this->createFormBuilder($category, [
                    'data_class' => 'Webkul\UVDeskSupportCenterBundle\Entity\SolutionCategory',
                    'csrf_protection' => false,
                    'allow_extra_fields' => true
                ])
                    ->add('name', 'text')
                    ->add('description', 'textarea')
                    ->getForm();

                $form->submit($content);
                $form->handleRequest($request);
                if ($form->isValid()) {
                    $this->entityManager->persist($category);
                    $this->entityManager->flush();

                    $json['alertClass'] = 'success';
                    $json['alertMessage'] = $this->translator->trans('Success ! Category updated successfully.');
                } else {
                    $json['alertClass'] = 'danger';
                    $json['errors'] = json_encode($this->getFormErrors($form));
                }
            } else {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] =  $this->translator->trans('Error ! Category does not exist.');
            }
        } elseif ($request->getMethod() == "PATCH") { //UPDATE STATUS
            $content = json_decode($request->getContent(), true);
            $id = $content['id'];
            $category = $this->entityManager->getRepository(SupportEntities\SolutionCategory::class)->find($id);

            if ($category) {
                switch ($content['editType']) {
                    case 'status':
                        $category->setStatus($content['value']);
                        $this->entityManager->persist($category);
                        $this->entityManager->flush();

                        $json['alertClass'] = 'success';
                        $json['alertMessage'] = $this->translator->trans('Success ! Category status updated successfully.');
                        break;
                    default:
                        break;
                }
            } else {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = $this->translator->trans('Error ! Category is not exist.');
            }
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    private function removeCategory($category)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $this->entityManager
            ->getRepository(SupportEntities\SolutionCategory::class)
            ->removeEntryByCategory($category);
    }

    /**
     * If customer is playing with url and no result is found then what will happen
     * @return
     */
    protected function noResultFound()
    {
        throw new NotFoundHttpException('Not Found!');
    }
}
