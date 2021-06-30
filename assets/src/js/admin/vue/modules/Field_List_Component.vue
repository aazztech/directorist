<template>
    <div class="directorist-form-fields-area" v-if="field_list && typeof field_list === 'object'">
        <component
            v-for="( field, field_key ) in field_list"
            :key="field_key"
            :is="field.type + '-field'"
            :section-id="sectionId"
            :field-id="field_key"
            :root="fieldList"
            v-bind="excludeShowIfCondition( field )"
            @update="update( { key: field_key, value: $event } )"
        />
    </div>
</template>

<script>
import helpers from './../mixins/helpers';

export default {
    name: 'field-list-compnents',
    model: {
        prop: 'value',
        event: 'update'
    },
    mixins: [ helpers ],
    props: {
        root: {
            default: ''
        },
        sectionId: {
            default: ''
        },
        fieldList: {
            default: ''
        },
        value: {
            default: ''
        }
    },

    created() {
        this.filtereFieldList();
    },

    watch: {
        fieldList() {
            this.filtereFieldList();
        },
        value() {
            this.filtereFieldList();
        }
    },

    computed: {
        rootFields() {
            if ( ! this.root ) { return this.value; }
            if ( typeof this.root !== 'object' ) { return this.value; }

            return this.root;
        }
    },

    data() {
        return {
            field_list: null
        }
    },

    methods: {
        filtereFieldList() {
            this.field_list = this.getFiltereFieldList( this.fieldList );
        },

        excludeShowIfCondition( field ) {

            if ( ! field ) {
                return field;
            }

            if ( typeof field !== 'object' ) {
                return field;
            }

            if ( field.showIf ) {
                delete field['showIf'];
            }

            if ( field.show_if ) {
                delete field['show_if'];
            }

            return field;
        },

        getFiltereFieldList( field_list ) {
            
            if ( ! field_list ) { return field_list }

            let new_fields = JSON.parse( JSON.stringify( this.fieldList ) );

            for ( let field_key in new_fields ) {
                if ( this.value && (typeof this.value === 'object') && (typeof this.value[ field_key ] !== 'undefined') ) {
                    new_fields[ field_key ].value = this.value[ field_key ];
                }
            }

            for ( let field_key in new_fields ) {
                if ( this.value && (typeof this.value === 'object') && (typeof this.value[ field_key ] !== 'undefined') ) {
                    new_fields[ field_key ].value = this.value[ field_key ];
                }
            }
            
            for ( let field_key in new_fields ) {
                if ( ! ( new_fields[ field_key ].showIf || new_fields[ field_key ].show_if ) ) { continue; }

                let show_if_condition = ( new_fields[ field_key ].showIf ) ? new_fields[ field_key ].showIf : new_fields[ field_key ].show_if;

                let checkShowIfCondition = this.checkShowIfCondition({ 
                    root: new_fields, 
                    condition: show_if_condition
                });

                if ( ! checkShowIfCondition.status ) {
                    delete new_fields[ field_key ];
                }
            }

            return new_fields;
        },

        update( payload ) {
            this.$emit( 'update', payload );
            this.filtereFieldList();
        }
    }
}
</script>