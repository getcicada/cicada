import { mount } from '@vue/test-utils';
import extensionStore from 'src/module/sw-extension/store/extensions.store';

const userInfo = {
    avatarUrl: 'https://avatar.url',
    email: 'max@muster.com',
    name: 'Max Muster',
};

async function createWrapper() {
    return mount(
        await wrapTestComponent('sw-extension-my-extensions-account', {
            sync: true,
        }),
        {
            global: {
                stubs: {
                    'sw-text-field': {
                        props: ['value'],
                        template: `
                    <input type="text" :value="value" @input="$emit('update:value', $event.target.value)" />
                `,
                    },
                    'sw-password-field': {
                        props: ['value'],
                        template: `
<input type="password" :value="value" @input="$emit('update:value', $event.target.value)" />
`,
                    },
                    'sw-skeleton': true,
                    'sw-avatar': true,
                    'sw-button': {
                        template: '<button @click="$emit(\'click\')"><slot></slot></button>',
                    },
                    'sw-meteor-card': {
                        template: '<div><slot></slot></div>',
                    },
                },
                provide: {
                    cicadaExtensionService: {
                        checkLogin: () => {
                            return Promise.resolve({
                                userInfo,
                            });
                        },
                    },
                    systemConfigApiService: {
                        getValues: () => {
                            return Promise.resolve({
                                'core.store.apiUri': 'https://api.cicada.com',
                                'core.store.licenseHost': 'sw6.test.cicada.in',
                                'core.store.shopSecret': 'very.s3cret',
                            });
                        },
                    },
                    storeService: {
                        login: (cicadaId, password) => {
                            if (cicadaId !== 'max@muster.com') {
                                return Promise.reject();
                            }
                            if (password !== 'v3ryS3cret') {
                                return Promise.reject();
                            }

                            Cicada.State.get('cicadaExtensions').userInfo = userInfo;

                            return Promise.resolve();
                        },
                        logout: () => {
                            Cicada.State.get('cicadaExtensions').userInfo = null;

                            return Promise.resolve();
                        },
                    },
                },
            },
        },
    );
}

/**
 * @package checkout
 */
describe('src/module/sw-extension/page/sw-extension-my-extensions-account', () => {
    beforeAll(async () => {
        Cicada.State.registerModule('cicadaExtensions', extensionStore);
    });

    beforeEach(async () => {
        Cicada.State.get('cicadaExtensions').userInfo = null;
    });

    it('should show the login fields when not logged in', async () => {
        const wrapper = await createWrapper();

        const cicadaIdField = wrapper.find('.sw-extension-my-extensions-account__cicada-id-field');
        const passwordField = wrapper.find('.sw-extension-my-extensions-account__password-field');
        const loginButton = wrapper.find('.sw-extension-my-extensions-account__login-button');

        // check if fields exists when user is not logged in
        expect(cicadaIdField.isVisible()).toBe(true);
        expect(passwordField.isVisible()).toBe(true);
        expect(loginButton.isVisible()).toBe(true);
    });

    it('should login when user clicks login', async () => {
        const wrapper = await createWrapper();

        // check if login status is not visible
        let loginStatus = wrapper.find('.sw-extension-my-extensions-account__wrapper-content-login-status-id');
        expect(loginStatus.exists()).toBe(false);

        // get fields
        const cicadaIdField = wrapper.get('.sw-extension-my-extensions-account__cicada-id-field');
        const passwordField = wrapper.get('.sw-extension-my-extensions-account__password-field');
        const loginButton = wrapper.find('.sw-extension-my-extensions-account__login-button');

        // enter credentials
        await cicadaIdField.setValue('max@muster.com');
        await passwordField.setValue('v3ryS3cret');

        await wrapper.vm.$nextTick();
        await flushPromises();

        // login
        await loginButton.trigger('click');
        await flushPromises();

        // check if layout switches
        loginStatus = wrapper.find('.sw-extension-my-extensions-account__wrapper-content-login-status-id');
        expect(loginStatus.exists()).toBe(true);
        expect(loginStatus.text()).toBe('max@muster.com');
    });

    it('should show the logged in view when logged in', async () => {
        Cicada.State.get('cicadaExtensions').userInfo = userInfo;

        // create component with logged in view
        const wrapper = await createWrapper();
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        // check if layout shows the logged in information
        const loginStatus = wrapper.find('.sw-extension-my-extensions-account__wrapper-content-login-status-id');
        expect(loginStatus.exists()).toBe(true);
        expect(loginStatus.text()).toBe('max@muster.com');
    });

    it('should logout when user clicks logout button', async () => {
        Cicada.State.get('cicadaExtensions').userInfo = userInfo;

        // create component with logged in view
        const wrapper = await createWrapper();
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        // check if logout button exists
        let logoutButton = wrapper.find('.sw-extension-my-extensions-account__logout-button');
        expect(logoutButton.exists()).toBe(true);

        // click on logout
        await logoutButton.trigger('click');

        // check if logout button disappears
        logoutButton = wrapper.find('.sw-extension-my-extensions-account__logout-button');
        expect(logoutButton.exists()).toBe(false);

        // check if user is sees login view
        const loginButton = wrapper.find('.sw-extension-my-extensions-account__login-button');
        expect(loginButton.exists()).toBe(true);
    });
});