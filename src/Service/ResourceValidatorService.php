<?php

namespace App\Service;

use App\Entity\Resource;

class ResourceValidatorService
{
    /**
     * Règle 1 : Le titre est obligatoire et doit avoir au moins 3 caractères.
     * Règle 2 : La description est obligatoire et doit avoir au moins 10 caractères.
     * Règle 3 : Le type doit être 'article' ou 'video'.
     */
    public function validate(Resource $resource): bool
    {
        if (strlen(trim($resource->getTitle())) < 3) {
            throw new \InvalidArgumentException('Le titre doit contenir au moins 3 caractères.');
        }

        if (strlen(trim($resource->getDescription())) < 10) {
            throw new \InvalidArgumentException('La description doit contenir au moins 10 caractères.');
        }

        if (!in_array($resource->getType(), [Resource::TYPE_ARTICLE, Resource::TYPE_VIDEO], true)) {
            throw new \InvalidArgumentException('Le type doit être article ou video.');
        }

        return true;
    }
}
