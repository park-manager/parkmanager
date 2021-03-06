<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Security;

use ParkManager\UI\Web\Form\Type\Security\ConfirmPasswordResetType;
use ParkManager\UI\Web\Response\TwigResponse;
use Rollerworks\Bundle\RouteAutofillBundle\Response\RouteRedirectResponse;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class ConfirmPasswordResetAction
{
    /**
     * @Route(
     *     path="/password-reset/confirm/{token}",
     *     name="park_manager.security_confirm_password_reset",
     *     requirements={"token" = ".+"},
     *     methods={"GET", "POST"}
     * )
     *
     * @return RouteRedirectResponse|TwigResponse
     */
    public function __invoke(Request $request, string $token, FormFactoryInterface $formFactory)
    {
        $form = $formFactory->create(ConfirmPasswordResetType::class, ['reset_token' => $token]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return RouteRedirectResponse::toRoute('park_manager.security_login')
                ->withFlash('success', 'flash.password_reset_accepted');
        }

        $response = new TwigResponse('security/password_reset_confirm.html.twig', $form);
        $response->setPrivate();
        $response->setMaxAge(1);

        return $response;
    }
}
