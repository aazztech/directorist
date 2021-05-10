import props from './input-field-props.js';

import { mapGetters } from 'vuex';
import helpers from '../helpers';

export default {
    name: 'restore-field',
    mixins: [ props, helpers ],
    model: {
        prop: 'value',
        event: 'input'
    },
    props: {
        label: {
            type: String,
            required: false,
            default: '',
        },
    },

    data() {
        return {
            validation_message: null
        }
    },

    methods: {
        ...mapGetters([
            'getFieldsValue'
        ]),

        restore() {
            const self = this;

            if ( ! ( this.restorData && typeof this.restorData === 'object' ) ) {
                console.log( 'Invalid Data' );
                this.validation_message = { type: 'error', message: 'Invalid Data' };

                setTimeout(() => {
                    self.validation_message = null;
                }, 5000);

                return;
            }
            
            let fields = {};
            for ( let field in this.restorData ) {
                fields[ field ] = this.maybeJSON( this.restorData[ field ] );
            }

            this.$store.commit( 'importFields', fields );
            this.$emit( 'do-action', { action: 'updateData', component: 'root' } );

            setTimeout(() => {
                self.validation_message = null;
            }, 5000);
        },
    }
}