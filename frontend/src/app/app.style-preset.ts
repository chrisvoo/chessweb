import { definePreset } from '@primeng/themes';
import Aura from '@primeng/themes/aura';

export default definePreset(Aura, {
  semantic: {
    primary: {
      50: '{orange.50}',
      100: '{orange.100}',
      200: '{orange.200}',
      300: '{orange.300}',
      400: '{orange.400}',
      500: '{orange.500}',
      600: '{orange.600}',
      700: '{orange.700}',
      800: '{orange.800}',
      900: '{orange.900}',
      950: '{orange.950}'
    },
    // colorScheme: {
    //   light: {
    //     primary: {
    //       color: '{zinc.950}',
    //       inverseColor: '#ffffff',
    //       hoverColor: '{zinc.900}',
    //       activeColor: '{zinc.800}'
    //     },
    //     highlight: {
    //       background: '{zinc.950}',
    //       focusBackground: '{zinc.700}',
    //       color: '#ffffff',
    //       focusColor: '#ffffff'
    //     }
    //   },
    //   dark: {
    //     primary: {
    //       color: '{zinc.50}',
    //       inverseColor: '{zinc.950}',
    //       hoverColor: '{zinc.100}',
    //       activeColor: '{zinc.200}'
    //     },
    //     highlight: {
    //       background: 'rgba(250, 250, 250, .16)',
    //       focusBackground: 'rgba(250, 250, 250, .24)',
    //       color: 'rgba(255,255,255,.87)',
    //       focusColor: 'rgba(255,255,255,.87)'
    //     }
    //   }
    // }
  },
  components: {
    tooltip: {
      padding: '5px',
      css: () => `
      .p-tooltip {
        font-size: 12px;
      }
      .p-tooltip-text {
        white-space: nowrap;
        width: fit-content;
      }
      `
    },
    inputtext: {
      focus: {
        border: {
          color: 'grey'
        }
      }
    },
    divider: {
      content: {
        color: 'black'
      }
    },
    confirmdialog: {
      icon: {
        size: '1em',
      }
    },
    datatable: {
      header: {
        padding: '0 0 1em 0'
      }
    },
    autocomplete: {
      padding: {
        x: 0, y: 0
      }
    }
  }
});
