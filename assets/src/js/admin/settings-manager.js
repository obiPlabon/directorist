import Vue from 'vue';
import Vuex from 'vuex';
import SlideUpDown from 'vue-slide-up-down';

Vue.use(Vuex);
Vue.component('slide-up-down', SlideUpDown);

import './vue/global-component';
import store from './vue/store/CPT_Manager_Store'
import settings_manager_component from './vue/apps/settings-manager/Settings_Manager.vue';

window.addEventListener('DOMContentLoaded', () => {
    const settings_panel_el = document.getElementById( 'atbdp-settings-manager' );

    if ( settings_panel_el ) {
        const encodedBuilderData = settings_panel_el.getAttribute( 'data-builder-data' );
        let builderData = atob( encodedBuilderData );

        try {
            builderData = JSON.parse( builderData );
        } catch ( error ) {
            builderData = [];
        }

        new Vue({
            el:'#atbdp-settings-manager',
            store,
            components: {
                'settings-manager': settings_manager_component
            },

            data() {
                return {
                    id: builderData.id,
                    fields: builderData.fields,
                    layouts: builderData.layouts,
                    config: builderData.config,
                }
            }
        });
    }

    /* Copy shortcodes on click */
    var $ = jQuery;
    $('body').on('click', '.atbdp_shortcodes', function () {
        const $this = $(this);
        const $temp = $('<input>');
        $('body').append($temp);
        $temp.val($(this).text()).select();
        document.execCommand('copy');
        $temp.remove();
        $(this).after(
            "<p class='copy-notify' style='color: #32cc6f; margin-top: 5px;'>Copied to clipboard!</p>"
        );
        setTimeout(function () {
            $this.siblings('.copy-notify').fadeOut(300, function () {
                $(this).remove();
            });
        }, 3000);
    });
});

