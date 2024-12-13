/**
 * @package services-settings
 */
import template from './sw-custom-field-translated-labels.html.twig';
import './sw-custom-field-translated-labels.scss';

const { Mixin } = Cicada;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    inject: ['acl'],

    mixins: [
        Mixin.getByName('sw-inline-snippet'),
    ],

    props: {
        locales: {
            type: Array,
            required: true,
            default: [],
        },
        config: {
            type: Object,
            required: true,
        },
        propertyNames: {
            type: Object,
            required: true,
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    computed: {
        fallbackLocale() {
            return this.$root.$i18n.fallbackLocale;
        },

        localeCount() {
            return this.locales.length;
        },
    },

    watch: {
        locales() {
            this.initializeConfiguration();
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initializeConfiguration();
        },

        initializeConfiguration() {
            Object.keys(this.propertyNames).forEach((property) => {
                if (!this.config.hasOwnProperty(property)) {
                    if (this.isCompatEnabled('INSTANCE_SET')) {
                        this.$set(this.config, property, {
                            [this.fallbackLocale]: null,
                        });
                    } else {
                        this.config[property] = { [this.fallbackLocale]: null };
                    }
                }
            });
        },

        getLabel(label, locale) {
            const snippet = this.getInlineSnippet(label);
            const language = this.$tc(`locale.${locale}`);

            return `${snippet} (${language})`;
        },

        onInput(input, propertyName, locale) {
            if (input === '') {
                this.config[propertyName][locale] = null;
            }
        },
    },
};