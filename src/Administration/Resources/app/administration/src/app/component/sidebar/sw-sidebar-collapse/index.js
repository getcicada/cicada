/**
 * @package admin
 */

import template from './sw-sidebar-collapse.html.twig';
import './sw-sidebar-collapse.scss';

const { Component } = Cicada;

/**
 * @private
 */
Component.extend('sw-sidebar-collapse', 'sw-collapse', {
    template,

    emits: ['change-expanded'],

    props: {
        expandChevronDirection: {
            type: String,
            required: false,
            default: 'right',
            validator: (value) =>
                [
                    'up',
                    'left',
                    'right',
                    'bottom',
                ].includes(value),
        },
    },

    computed: {
        expandButtonClass() {
            return {
                'is--hidden': this.expanded,
            };
        },

        collapseButtonClass() {
            return {
                'is--hidden': !this.expanded,
            };
        },
    },

    methods: {
        collapseItem() {
            this.$super('collapseItem');
            this.$emit('change-expanded', { isExpanded: this.expanded });
        },
    },
});