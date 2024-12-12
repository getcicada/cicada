import { type PropType } from 'vue';
import template from './sw-cms-visibility-config.html.twig';
import './sw-cms-visibility-config.scss';
import type CmsVisibility from '../../shared/CmsVisibility';

/**
 * @private
 * @package buyers-experience
 */
export default Cicada.Component.wrapComponentConfig({
    template,

    compatConfig: Cicada.compatConfig,

    props: {
        visibility: {
            type: Object as PropType<CmsVisibility>,
            required: true,
        },
    },
    methods: {
        onVisibilityChange(viewport: string, isVisible: boolean) {
            this.$emit('visibility-change', viewport, isVisible);
        },
    },
});
