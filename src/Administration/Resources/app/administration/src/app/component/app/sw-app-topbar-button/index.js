/**
 * @package customer-order
 */

import template from './sw-app-topbar-button.html.twig';
import './sw-app-topbar-button.scss';

const { Component } = Cicada;

/**
 * @private
 * @description Apply for upselling service only, no public usage
 */
Component.register('sw-app-topbar-button', {
    template,

    computed: {
        topBarButtons() {
            return Cicada.Store.get('topBarButton').buttons;
        },
    },

    methods: {
        async runAction(button) {
            if (typeof button.callback !== 'function') {
                return;
            }

            button.callback();
        },
    },
});