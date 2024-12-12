import template from './sw-base-filter.html.twig';
import './sw-base-filter.scss';

const { Component } = Cicada;

/**
 * @private
 */
Component.register('sw-base-filter', {
    template,

    compatConfig: Cicada.compatConfig,

    emits: ['filter-reset'],

    props: {
        title: {
            type: String,
            required: true,
        },
        showResetButton: {
            type: Boolean,
            required: true,
        },
        active: {
            type: Boolean,
            required: true,
        },
    },

    watch: {
        active(value) {
            if (!value) {
                this.resetFilter();
            }
        },
    },

    methods: {
        resetFilter() {
            this.$emit('filter-reset');
        },
    },
});