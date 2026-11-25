<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\ArchiveDocument;
use App\Entity\ArchiveFolder;
use App\Entity\ArchivePermission;
use App\Entity\ArchiveProject;
use App\Entity\ArchiveYear;
use App\Entity\User;
use App\Enum\ArchiveFolderPermission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ArchiveVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        $attributes = array_map(
            static fn (ArchiveFolderPermission $permission): string => $permission->value,
            ArchiveFolderPermission::cases(),
        );

        return in_array($attribute, $attributes, true)
            && ($subject instanceof ArchiveFolder
                || $subject instanceof ArchiveDocument
                || $subject instanceof ArchiveProject
                || $subject instanceof ArchiveYear);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || !$user->isActive()) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        $folder = match (true) {
            $subject instanceof ArchiveFolder => $subject,
            $subject instanceof ArchiveDocument => $subject->getFolder(),
            default => null,
        };

        $project = match (true) {
            $subject instanceof ArchiveProject => $subject,
            $folder?->getArchiveProject() => $folder->getArchiveProject(),
            default => null,
        };

        $year = $subject instanceof ArchiveYear ? $subject : $project?->getArchiveYear();
        $readOnly = in_array($attribute, [ArchiveFolderPermission::VIEW->value, ArchiveFolderPermission::EXPORT->value], true);

        if (null !== $year && $year->isLocked() && !$readOnly) {
            return false;
        }

        for ($current = $folder; null !== $current; $current = $current->getParent()) {
            if ($current->isRestrictedToAdmin()) {
                return false;
            }
        }

        if (ArchiveFolderPermission::DELETE->value === $attribute) {
            return false;
        }

        if (in_array('ROLE_COMM', $user->getRoles(), true)
            && in_array($attribute, [
                ArchiveFolderPermission::VIEW->value,
                ArchiveFolderPermission::UPLOAD->value,
                ArchiveFolderPermission::EDIT->value,
                ArchiveFolderPermission::SOFT_DELETE->value,
                ArchiveFolderPermission::EXPORT->value,
            ], true)) {
            return true;
        }

        $permissions = [];
        if (null !== $project) {
            foreach ($project->getPermissions() as $permission) {
                $permissions[] = $permission;
            }
        }
        for ($current = $folder; null !== $current; $current = $current->getParent()) {
            foreach ($current->getPermissions() as $permission) {
                $permissions[] = $permission;
            }
        }

        /** @var ArchivePermission $permission */
        foreach ($permissions as $permission) {
            if ($permission->getUser() === $user && $permission->allows($attribute)) {
                return true;
            }
        }

        return false;
    }
}
