import { mapState } from 'vuex';

export default {
    computed: {
        ...mapState({
            fields: 'fields',
        }),
    },

    methods: {
        isObject( the_var ) {
            if ( typeof the_var === 'undefined' ) { return false }
            if ( the_var === null ) { return false }
            if ( typeof the_var !== 'object' ) { return false }
            if ( Array.isArray( the_var ) ) { return false }

            return the_var;
        },

        mapDataByMap( data, map ) {
            const flatten_data = JSON.parse( JSON.stringify( data ) );
            const flatten_map = JSON.parse( JSON.stringify( map ) );
           
            let mapped_data = flatten_data.map( element => {
                let item = {};

                for ( let key in flatten_map) {
                    if ( typeof element[ key ] !== 'undefined' ) {
                        item[ key ] = element[ flatten_map[ key ] ];
                    }
                }

                return item;
            });

            return mapped_data;
        },

        filterDataByValue( data, value ) {
            let value_is_array = ( value && typeof value === 'object' ) ? true : false;
            let value_is_text  = ( typeof value === 'string' || typeof value === 'number' ) ? true : false;
            let flatten_data   = JSON.parse( JSON.stringify( data ) );

            return flatten_data.filter( item => {
                if ( value_is_text && value ===  item.value ) {
                    // console.log( 'value_is_text', item.value, value );
                    return item;
                }
                
                if ( value_is_array && value.includes( item.value ) ) {
                    // console.log( 'value_is_array', item.value, value );
                    return item;
                }

                if ( ! value_is_text && ! value_is_array ) {
                    // console.log( 'no filter', item.value, value );   
                    return item;
                }

            });
        },

        checkShowIfCondition( condition ) {
            // console.log( { condition } );
            
            let failed_cond_count   = 0;
            let success_cond_count  = 0;
            let accepted_comparison = [ 'and', 'or' ];
            let matched_data        = [];
        
            let state = {
                failed_conditions: failed_cond_count,
                successed_conditions: success_cond_count,
                matched_data: matched_data,
            };
            
            let compare      = 'and';
            let target_field = this.getTergetFields( condition.where );
        
            if ( ! ( condition.conditions && Array.isArray( condition.conditions ) && condition.conditions.length ) ) { return state; }
            if ( ! this.isObject( target_field ) ) { return state; }
        
            // console.log( { target_field } );
        
            if ( typeof condition.compare === 'string' && accepted_comparison.indexOf( condition.compare ) ) {
                compare = condition.compare;
            }
        
            for ( let sub_condition of condition.conditions ) {
        
                if ( typeof sub_condition.key !== 'string' ) {
                continue;
                }
        
                let sub_condition_field_path = sub_condition.key.split('.');
                let sub_condition_field = null;
                let sub_condition_error = 0;
                
                if ( ! sub_condition_field_path.length ) {
                continue;
                }
                
                // ---
                if ( sub_condition_field_path[0] !== '_any' ) {
                sub_condition_field = target_field[ sub_condition_field_path[0] ];
        
                if ( sub_condition_field_path.length > 1 && ! this.isObject( sub_condition_field ) ) {
                    sub_condition_error++;
                }
        
                if ( sub_condition_field_path.length > 1 && ! sub_condition_error ) {
                    sub_condition_field = target_field[ sub_condition_field_path[0] ][ sub_condition_field_path[1] ];
                }
        
                if ( typeof sub_condition_field === 'undefined' ) {
                    sub_condition_error++;
                }
        
                if ( sub_condition_error ) {
                    failed_cond_count++;
                    continue;
                }
        
                if ( sub_condition.value !== sub_condition_field ) {
                    failed_cond_count++;
                    continue;
                }
        
                matched_data.push( target_field[ sub_condition_field_path[0] ] );
                success_cond_count++;
                continue;
                }
        
                // Check if has _any condition
                if ( sub_condition_field_path[0] === '_any' ) {
                let failed_any_cond_count = 0;
                let success_any_cond_count = 0;
        
                for ( let field in target_field ) {
                    let any_cond_error = 0;
                    sub_condition_field = target_field[ field ];
                    // console.log( { sub_condition_field, field } );
        
                    if ( sub_condition_field_path.length > 1 && ! this.isObject( sub_condition_field ) ) {
                    any_cond_error++;
                    }
        
                    if ( sub_condition_field_path.length > 1 && ! any_cond_error ) {
                    sub_condition_field = sub_condition_field[ sub_condition_field_path[1] ];
                    } 
        
                    if ( typeof sub_condition_field === 'undefined' ) {
                    any_cond_error++;
                    }
        
                    // console.log( { sub_condition_field, failed_any_cond_count } );
        
                    if ( any_cond_error ) {
                    failed_any_cond_count++;
                    continue;
                    }
        
                    if ( sub_condition.value !== sub_condition_field ) {
                    failed_any_cond_count++;
                    continue;
                    }
                    
                    matched_data.push( target_field[ field ] );
                    success_any_cond_count++;
                }
        
                if ( ! success_any_cond_count ) { failed_cond_count++; } 
                    else { success_cond_count++; }
                }
        
            }
        
            // Get Status
            switch ( compare ) {
                case 'and':
                status = ( failed_cond_count ) ? false : true;
                break;
                case 'or':
                status = ( success_cond_count ) ? true : false;
                break;
                default:
                status = false;
            }
        
            state = {
                status: status,
                failed_conditions: failed_cond_count,
                successed_conditions: success_cond_count,
                matched_data: matched_data,
            };
        
            // console.log( { state } );
        
            return state;
        },

        getFormFieldName( field_type ) {
            return field_type + '-field';
        },

        updateFieldValue( field_key, value ) {
            this.$store.commit( 'updateFieldValue', { field_key, value } );
        },
        
        getActiveClass( item_index, active_index ) {
            return ( item_index === active_index ) ? 'active' : '';
        },

        getTergetFields( fields ) {
            
            if ( typeof fields !== 'string' ) { return null; }
            let terget_field = null;

            let terget_fields = fields.split('.');
            let terget_missmatched = false;

            if ( terget_fields && typeof terget_fields === 'object'  ) {
                terget_field = this.fields;

                for ( let key of terget_fields ) {
                    
                    if ( typeof terget_field[ key ] === 'undefined' ) {
                        terget_missmatched = true;
                        break;
                    }

                    terget_field = ( terget_field !== null ) ? terget_field[ key ] : this.fields[ key ];
                }
            }

            if ( terget_missmatched ) { return false; }

            return JSON.parse( JSON.stringify( terget_field ) );
        },

        getSanitizedProps( props ) {

            if ( props && typeof props === 'object' ) {
                let _props = JSON.parse( JSON.stringify( props ) );
                delete _props.value;

                return _props;
            }

            return props;
        }
    },

    data() {
        return {
            default_option: { value: '', label: 'Select...' },
        }
    },
}