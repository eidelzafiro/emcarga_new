import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import PrimeVue from 'primevue/config';
import AuraPreset from '@primeuix/themes/aura';

import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import ToastService from 'primevue/toastservice';
import Card from 'primevue/card';
import InputNumber from 'primevue/inputnumber';
import Password from 'primevue/password';
import ToggleSwitch from 'primevue/toggleswitch';
import PanelMenu from 'primevue/panelmenu';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import Drawer from 'primevue/drawer';
import Tooltip from 'primevue/tooltip';

const appName = import.meta.env.VITE_APP_NAME || 'EMCARGA';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        app.use(PrimeVue, {
            theme: {
                preset: AuraPreset,
                options: {
                    darkModeSelector: '.p-dark',
                },
            },
            locale: {
                accept: 'Sí',
                reject: 'No',
                startsWith: 'Empieza con',
                contains: 'Contiene',
                notContains: 'No contiene',
                endsWith: 'Termina con',
                equals: 'Igual',
                notEquals: 'No igual',
                noFilter: 'Sin filtro',
                lt: 'Menor que',
                lte: 'Menor o igual que',
                gt: 'Mayor que',
                gte: 'Mayor o igual que',
                dateIs: 'Fecha es',
                dateIsNot: 'Fecha no es',
                dateBefore: 'Fecha antes',
                dateAfter: 'Fecha después',
                clear: 'Limpiar',
                apply: 'Aplicar',
                matchAll: 'Coincidir todos',
                matchAny: 'Coincidir cualquiera',
                addRule: 'Agregar regla',
                removeRule: 'Eliminar regla',
                choose: 'Elegir',
                upload: 'Subir',
                cancel: 'Cancelar',
                dayNames: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
                dayNamesShort: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
                dayNamesMin: ['D', 'L', 'M', 'X', 'J', 'V', 'S'],
                monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                monthNamesShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                chooseYear: 'Elegir año',
                chooseMonth: 'Elegir mes',
                chooseDate: 'Elegir fecha',
                prevDecade: 'Década anterior',
                nextDecade: 'Década siguiente',
            },
        });

        app.use(ToastService);
        app.directive('tooltip', Tooltip);

        app.component('Button', Button);
        app.component('InputText', InputText);
        app.component('Textarea', Textarea);
        app.component('Select', Select);
        app.component('Dialog', Dialog);
        app.component('DataTable', DataTable);
        app.component('Column', Column);
        app.component('Tag', Tag);
        app.component('Toast', Toast);
        app.component('Card', Card);
        app.component('InputNumber', InputNumber);
        app.component('Password', Password);
        app.component('ToggleSwitch', ToggleSwitch);
        app.component('PanelMenu', PanelMenu);
        app.component('Avatar', Avatar);
        app.component('Badge', Badge);
        app.component('Drawer', Drawer);

        app.use(plugin);
        app.mount(el);
    },
    progress: {
        color: '#10b981',
    },
});
