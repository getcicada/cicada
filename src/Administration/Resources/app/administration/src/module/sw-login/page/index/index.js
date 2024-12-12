/**
 * @package admin
 */

import template from './sw-login.html.twig';
import './sw-login.scss';

const { Component } = Cicada;

/**
 * @private
 * @package admin
 */
Component.register('sw-login', {
    template,

    compatConfig: Cicada.compatConfig,

    props: {
        hash: {
            type: String,
            default: null,
        },
    },

    data() {
        return {
            shouldRenderDOM: false,
            isLoading: false,
            isLoginSuccess: false,
            isLoginError: false,
        };
    },

    metaInfo() {
        return {
            title: this.title,
        };
    },

    computed: {
        title() {
            const modulName = this.$tc('sw-login.general.mainMenuItemIndex');
            const adminName = this.$tc('global.sw-admin-menu.textCicadaAdmin');

            return `${modulName} | ${adminName}`;
        },
    },

    beforeMount() {
        const cookieStorage = Cicada.Service('loginService').getStorage();
        const refreshAfterLogout = cookieStorage.getItem('refresh-after-logout');

        if (refreshAfterLogout) {
            cookieStorage.removeItem('refresh-after-logout');
            window.location.reload();
        } else {
            this.shouldRenderDOM = true;
        }
    },

    methods: {
        setLoading(val) {
            this.isLoading = val;
        },

        loginError() {
            this.isLoginError = !this.isLoginError;
        },

        loginSuccess() {
            this.isLoginSuccess = !this.isLoginSuccess;
        },
    },
});