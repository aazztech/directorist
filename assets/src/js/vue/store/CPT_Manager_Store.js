import Vue from 'vue';
import Vuex from 'vuex';

Vue.use(Vuex)

export default new Vuex.Store({
  // state
  state: {
    active_nav_index: 0,
    fields: {},
    layouts: {},
    options: {},
    config: {},
    cached_fields: {},
    highlighted_field_key: '',
    metaKeys: {},
    deprecatedMetaKeys: [],
    sidebarNavigation: {},
  },
  
  // mutations
  mutations: {
    prepareNav: ( state ) => {
      let menu_count = 0;

      let prepare_section_fields = function( args ) {
        let sections    = args.sections;
        let menu_key    = args.menu_key;
        let submenu_key = ( args.submenu_key ) ? args.submenu_key : '';


        for ( let section_key in sections ) {
          if ( sections[ section_key ].fields ) {
            for ( let field_key of sections[ section_key ].fields ) {
              if ( ! state.cached_fields[ field_key ]  ) { continue; }
              let hash = menu_key;

              if ( submenu_key ) {
                hash = hash + '__' + submenu_key;
              }

              hash = hash + '__' + section_key + '__' + field_key;

              state.cached_fields[ field_key ].layout_path = {
                menu_key: menu_key,
                submenu_key: submenu_key,
                section_key: section_key,
                field_key: field_key,
                hash: hash
              };
            }
          }
        }
      };

      
      for ( let menu_key in state.layouts ) {
        let status = ( 0 === menu_count ) ? true : false;
        Vue.set( state.layouts[ menu_key ], 'active', status );

        if ( state.layouts[ menu_key ].sections ) {
          prepare_section_fields({ 
            menu_key: menu_key, 
            sections: state.layouts[ menu_key ].sections,
          });
        }
    
        if ( state.layouts[ menu_key ].submenu ) {
            let submenu_count = 0;
            for ( let submenu_key in state.layouts[ menu_key ].submenu ) {

              let status = ( 0 === menu_count && 0 === submenu_count ) ? true : false;
              Vue.set( state.layouts[ menu_key ].submenu[ submenu_key ], 'active', status );
              submenu_count++;

              if ( state.layouts[ menu_key ].submenu[ submenu_key ].sections ) {
                prepare_section_fields({ 
                  menu_key: menu_key, 
                  submenu_key: submenu_key,
                  sections: state.layouts[ menu_key ].submenu[ submenu_key ].sections
                });
              }
            }
        }

        menu_count++;
      }
    },

    cacheFieldsData: ( state ) => {
      state.cached_fields = JSON.parse( JSON.stringify( state.fields ) );
    },

    resetHighlightedFieldKey: ( state ) => {
      state.highlighted_field_key = '';
    },

    updateCachedFieldData: ( state, payload ) => {
      state.cached_fields[ payload.key ].value = payload.value;
    },

    swichToNav( state, payload ) {
      let menu_key    = payload.menu_key;
      let submenu_key = payload.submenu_key;

      state.highlighted_field_key = '';

      const highlight_active_field = function( hash ) {

        let hash_paths = hash.split( '__' );
        let index      = hash_paths.length - 1;
        let field_key  = hash_paths[ index ];

        if ( ! state.cached_fields[ field_key ] ) { return; }

        state.highlighted_field_key = field_key;
      };

      if ( ! state.layouts[ menu_key ] ) { return; }

      // Active Top Menu
      for ( let menu in state.layouts ) {
        Vue.set( state.layouts[ menu ], 'active', false );
      }

      Vue.set( state.layouts[ menu_key ], 'active', true );

      // Active Sub Menu
      if ( ! submenu_key && state.layouts[ menu_key ].submenu ) {
        let submenu_keys = Object.keys( state.layouts[ menu_key ].submenu );
        submenu_key = ( Array.isArray( submenu_keys ) ) ? submenu_keys[0] : null;
      }

      let hash = ( payload.hash ) ? '#' + payload.hash : '#' + menu_key;

      if ( ! submenu_key ) {
        window.location.hash = hash;
        highlight_active_field( hash, submenu_key ); 
        return;
      }


      for ( let submenu in state.layouts[ menu_key ].submenu ) {
        Vue.set( state.layouts[ menu_key ].submenu[ submenu ], 'active', false );
      }

      Vue.set( state.layouts[ menu_key ].submenu[ submenu_key ], 'active', true );
      hash = ( payload.hash ) ? '#' + payload.hash : '#' + menu_key + '__' + submenu_key;

      highlight_active_field( hash );

      window.location.hash = hash;
    },

    swichNav: ( state, index ) => {
      state.active_nav_index = index;
    },

    setMetaKey: ( state, payload ) => {
      Vue.set( state.metaKeys, payload.key, payload.value );
    },

    removeMetaKey: ( state, payload ) => {
      Vue.delete( state.metaKeys, payload.key );
    },

    updateOptionsField: ( state, payload ) => {
      state.options[ payload.field ].value = payload.value;
    },

    updateFields: ( state, value ) => {
      state.fields = value;
    },

    updatelayouts: ( state, value ) => {
      state.layouts = value;
    },

    updateOptions: ( state, value ) => {
      state.options = value;
    },

    updateConfig: ( state, value ) => {
      state.config = value;
    },

    updateFormFields: ( state, value ) => {
      state.form_fields = value;
    },

    updateFieldValue: ( state, payload ) => {
      Vue.set( state.fields[ payload.field_key ], 'value' , payload.value );
    },

    updateFieldData: ( state, payload ) => {
      Vue.set( state.fields[ payload.field_key ], payload.option_key , payload.value );
    },

    updateGeneralSectionData: ( state, payload ) => {
      state.layouts.general.submenu.general.sections[ payload.section_key ].fields[ payload.field_key ].value = payload.value;
    },

    importFields: ( state, importing_fields ) => {
      for ( let field_key in importing_fields ) {
        if ( typeof state.fields[ field_key ] === 'undefined' ) { continue; }

        Vue.set( state.fields[ field_key ], 'value' , importing_fields[ field_key ] );
      }
    },
  },

  getters: {
    getFieldsValue: state => {
      const maybeJSON = function ( data ) {
          let value = ( typeof data === 'undefined' ) ? '' : data;

          if ( 'object' === typeof value && Object.keys( value ).length ) {
              value = JSON.stringify( value );
          }
          
          if ( 'object' === typeof value && ! Object.keys( value ).length ) {
              value = '';
          }

          return value;
      };

      let form_data = {};
      for ( let field in state.fields ) {
          form_data[ field ] = maybeJSON( state.fields[ field ].value )
      }

      return form_data;
    }
  }

});
