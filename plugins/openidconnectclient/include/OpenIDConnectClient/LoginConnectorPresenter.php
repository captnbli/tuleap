<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\OpenIDConnectClient;


class LoginConnectorPresenter {

    private $providers_authorization_request_uri;

    public function __construct(array $providers_authorization_request_uri) {
        $this->providers_authorization_request_uri = $providers_authorization_request_uri;
    }

    /**
     * @return string
     */
    public function login_title() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient', 'login_with_openidconnect');
    }

    public function connect_with() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient', 'connect_with');
    }

    /**
     * @return array
     */
    public function providers() {
        return $this->providers_authorization_request_uri;
    }
}