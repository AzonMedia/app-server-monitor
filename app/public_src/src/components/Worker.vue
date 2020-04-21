<template>
    <div class="worker">

        <!-- :bg-variant="WorkerData.data.general.is_task_worker ? 'info' : 'primary' " -->
        <!-- :title="'Worker '+WorkerData.worker_id" -->
        <!-- :footer="(WorkerData.data.general.is_task_worker ? 'Task Worker' : 'Worker') + ' ' + WorkerData.worker_id" -->
            <b-card
                    no-body

                    header-border-variant="secondary"
            >

                <b-tabs card pills>

                    <b-tab :title="index" v-for="(TabData, index) in WorkerData.data">
                        <WorkerTableC v-bind:TableData="TabData"></WorkerTableC>
                    </b-tab>
                </b-tabs>
                <template v-slot:footer>
                    <b-form-checkbox v-model="$parent.selected_workers" :value="WorkerData.worker_id" unchecked-value="">{{(WorkerData.data.general.is_task_worker ? 'Task Worker' : 'Worker') + ' ' + WorkerData.worker_id}}</b-form-checkbox>

                </template>

            </b-card>
    </div>
</template>

<script>

    import WorkerTableC from '@GuzabaPlatform.AppServer.Monitor/components/WorkerTable.vue'

    export default {
        name: "Worker",
        components: {
            WorkerTableC,
        },
        props: {
            WorkerData: Object,
            //selected_workers: Array,//[Vue warn]: Avoid mutating a prop directly since the value will be overwritten whenever the parent component re-renders. Instead, use a data or computed property based on the prop's value. Prop being mutated: "selected_workers"
        },

        methods: {

        }
    }
</script>

<style scoped>
    .worker {
        margin: 10px;
        /* max-width: 500px; */
    }
    /*
    .worker {
        display: block;
        width: 400px;
        height: 50px;
        border: 1px solid blue;
        float: left;
        margin: 2px;
    }
     */
</style>