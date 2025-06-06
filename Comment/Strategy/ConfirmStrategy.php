<?php

/*
 * This file is part of the enhavo package.
 *
 * (c) WE ARE INDEED GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enhavo\Bundle\CommentBundle\Comment\Strategy;

use Enhavo\Bundle\AppBundle\Template\TemplateResolver;
use Enhavo\Bundle\CommentBundle\Comment\PublishStrategyInterface;
use Enhavo\Bundle\CommentBundle\Model\CommentInterface;
use Enhavo\Bundle\CommentBundle\Repository\CommentRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ConfirmStrategy implements PublishStrategyInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private TemplateResolver $templateResolver,
        private TranslatorInterface $translator,
        private CommentRepository $commentRepository,
        private array $mailerConfig,
    ) {
    }

    public function preCreate(CommentInterface $comment, array $options): void
    {
    }

    protected function getMailerTo($options)
    {
        return $options['mailer_to'];
    }

    protected function getMailerFrom($options)
    {
        if (null !== $options['mailer_from']) {
            return $options['mailer_from'];
        }

        return $this->mailerConfig['from'];
    }

    protected function getMailerSenderName($options)
    {
        if (null !== $options['mailer_sender_name']) {
            return $options['mailer_sender_name'];
        }

        return $this->mailerConfig['name'];
    }

    protected function getSubject($options)
    {
        return $this->translator->trans($options['mailer_subject'], [], $options['translation_domain']);
    }

    public function postCreate(CommentInterface $comment, array $options): void
    {
        $template = $this->templateResolver->resolve('mail/comment/confirm.html.twig');
        $pendingComments = $this->getPendingComments($comment);

        $content = $this->twig->render($template, [
            'comment' => $comment,
            'pendingComments' => $pendingComments,
        ]);

        $message = new Email();
        $message->subject($this->getSubject($options));
        $message->html($content);
        $message->to(new Address($this->getMailerTo($options)));
        $message->from(new Address($this->getMailerFrom($options), $this->getMailerSenderName($options)));

        $this->mailer->send($message);
    }

    private function getPendingComments(CommentInterface $newComment): array
    {
        $pendingComments = $this->commentRepository->findBy([
            'state' => CommentInterface::STATE_PENDING,
        ]);

        $comments = [];
        foreach ($pendingComments as $comment) {
            if ($newComment !== $pendingComments) {
                $comments[] = $comment;
            }
        }

        return $comments;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mailer_subject' => 'comment.label.confirm.subject',
            'translation_domain' => 'EnhavoCommentBundle',
            'mailer_from' => null,
            'mailer_sender_name' => null,
        ]);

        $resolver->setRequired(['mailer_to']);
    }
}
