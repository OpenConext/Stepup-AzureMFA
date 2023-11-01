<?php
/**
 * Copyright 2019 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\AzureMfa\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class LocaleController extends AbstractController
{
    /**
     * @Route("/local/{lang}", name="local")
     */
    public function localeAction(Request $request, string $lang): RedirectResponse|Response
    {
        // Make sure we redirect back to this host.
        $referer = $request->headers->get('referer');
        if ($request->getHost() !== parse_url($referer, PHP_URL_HOST)) {
            return new Response(sprintf('Cannot be requested from %s', $referer), Response::HTTP_BAD_REQUEST);
        }

        // Set local.
        $request->setLocale($lang);

        return new RedirectResponse($referer);
    }
}
