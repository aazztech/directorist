<template>
    <div class="cptm-form-group" :class="formGroupClass">
        <div class="atbdp-row">
            <div class="atbdp-col atbdp-col-4">
                <label v-if="( label.length )">
                    <component :is="labelType">{{ label }}</component>
                </label>
                
                <p class="cptm-form-group-info" v-if="description.length" v-html="description"></p>
            </div>

            <div class="atbdp-col atbdp-col-8">
                <input type="button" v-if="! generateShortcode" class="cptm-btn cptm-generate-shortcode-button" value="Generate Shortcode" @click="generate">
                <div v-if="generateShortcode" class="cptm-shortcode" ref="shortcode" @click="copyToClip">{{ shortcode }}</div>
                <div v-if="successMsg.length" class="cptm-info-text cptm-info-success">{{ successMsg }}</div>
            </div>
        </div>
    </div>
</template>

<script>
import text_feild from '../../../../mixins/form-fields/shortcode-field';

export default {
    name: 'shortcode-field-theme-butterfly',
    mixins: [ text_feild ],
}
</script>