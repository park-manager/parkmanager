<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Core\Infrastructure\DependencyInjection\Module\Traits;

use Rollerworks\Bundle\RouteAutowiringBundle\RouteImporter;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
trait RegisterSectionRoutesTrait
{
    /**
     * Registers the routes using the RouteImporter importer.
     *
     * Routing files are registers when they exists.
     */
    final protected function registerRoutes(RouteImporter $routeImporter, ?string $configDir): void
    {
        if (file_exists($configDir.'/routing/client.php')) {
            $routeImporter->import($configDir.'/routing/client.php', 'park_manager.client_section.root');
        }

        if (file_exists($configDir.'/routing/admin.php')) {
            $routeImporter->import($configDir.'/routing/admin.php', 'park_manager.admin_section.root');
        }

        if (file_exists($configDir.'/routing/api.php')) {
            $routeImporter->import($configDir.'/routing/api.php', 'park_manager.api_section.root');
        }
    }
}
