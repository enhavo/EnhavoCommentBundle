<?php
/**
 * Created by PhpStorm.
 * User: gseidel
 * Date: 2019-10-21
 * Time: 19:28
 */

namespace Enhavo\Bundle\CommentBundle\Widget;

use Enhavo\Bundle\AppBundle\Widget\AbstractWidgetType;
use Enhavo\Bundle\CommentBundle\Comment\CommentManager;
use Enhavo\Bundle\CommentBundle\Exception\CommentSubjectException;
use Enhavo\Bundle\CommentBundle\Model\CommentSubjectInterface;
use Enhavo\Bundle\CommentBundle\Repository\CommentRepository;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentsWidgetType extends AbstractWidgetType
{
    /**
     * @var CommentRepository
     */
    private $repository;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var CommentManager
     */
    private $commentManager;

    public function __construct(
        CommentRepository $repository,
        RequestStack $requestStack,
        CommentManager $commentManager
    ) {
        $this->repository = $repository;
        $this->requestStack = $requestStack;
        $this->commentManager = $commentManager;
    }

    public function createViewData(array $options, $resource = null)
    {
        $subject = $this->getSubject($options);
        $form = $options['form'] !== null ? $options['form'] : $this->commentManager->createSubmitForm();
        $action = $options['action'] !== null ? $options['action'] : $this->requestStack->getCurrentRequest()->getUri();

        $comments = $this->repository->findPublicByThread($subject->getThread());
        if($comments instanceof Pagerfanta) {
            $page = $options['page'];
            if($page === null) {
                $page = $this->requestStack->getCurrentRequest()->get($options['page_parameter']);
            }
            $comments->setCurrentPage($page ?  $page : 1);
            $comments->setMaxPerPage($options['limit']);
        }

        return [
            'resources' => $comments,
            'form' => $form->createView(),
            'action' => $action,
            'pageParameter' => $options['page_parameter']
        ];
    }

    private function getSubject($options): CommentSubjectInterface
    {
        if(!$options['subject'] instanceof CommentSubjectInterface) {
            throw CommentSubjectException::createTypeException($options['subject']);
        } elseif($options['subject']->getThread() === null) {
            throw CommentSubjectException::createNoThreadException($options['subject']);
        }

        return $options['subject'];
    }

    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setDefaults([
            'template' => 'theme/widget/comment/comments.html.twig',
            'pagination' => true,
            'page' => null,
            'page_parameter' => 'comment-page',
            'limit' => 10,
            'form' => null,
            'action' => null
        ]);

        $optionsResolver->setRequired('subject');
    }

    public function getType()
    {
        return 'comments';
    }
}
