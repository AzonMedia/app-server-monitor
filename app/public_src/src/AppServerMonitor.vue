<template>
    <div>
        <div>Application server monitor</div>

        <!--
        <b-dropdown id="dropdown-1" text="Workers Actions" class="m-md-2" variant="outline-primary">
        </b-dropdown>
        -->
        <div class="server">
            <h4>Server</h4>
            <div class="server-controls">
                <b-dropdown text="Server Actions" class="m-md-2" variant="outline-primary">
                    <b-dropdown-item v-for="(Action, index) in server_actions" :key="index" @click="handle_server_action(index)">{{Action.name}}</b-dropdown-item>
                </b-dropdown>
            </div>
            <ServerC v-bind:ServerData="Server.data" />
        </div>
        <div class="workers">
            <h4>Workers</h4>
            <div class="worker-controls">
                <b-dropdown text="Workers Actions" class="m-md-2" variant="outline-primary">
                    <b-dropdown-item v-for="(Action, index) in worker_actions" :key="index" @click="handle_worker_action(index)">{{Action.name}}</b-dropdown-item>
                </b-dropdown>
                <b-button variant="outline-primary" @click="handle_select_all">Select All</b-button>
                <b-button variant="outline-primary" @click="handle_unselect_all">Unselect All</b-button>
            </div>

            <b-card-group deck>
                <WorkerC v-for="(WorkerData, index) in workers" v-bind:WorkerData="WorkerData" v-bind:key="WorkerData.worker_id" />
            </b-card-group>

        </div>
        <ActionC v-bind:ActionData="ActionData" v-bind:selected_workers="selected_workers" v-bind:is_server_action="is_server_action"></ActionC>
    </div>
</template>

<script>
    import ServerC from '@GuzabaPlatform.AppServer.Monitor/components/Server.vue'
    import WorkerC from '@GuzabaPlatform.AppServer.Monitor/components/Worker.vue'
    import ActionC from '@GuzabaPlatform.AppServer.Monitor/components/Action.vue'

    export default {
        name: "AppServerMonitor",
        components: {
            ServerC,
            WorkerC,
            ActionC,

        },
        data() {
           return {
               timer: 0,
               selected_workers: [],
               workers: [],//data
               Server: {},//data
               worker_actions: [],//to be populated by the server
               server_actions: [],//to be populated by the server
               ActionData: {},//will be populated when an action is selected
               is_server_action: false,
               //reload_interval: 1, //in seconds
           }
        },
        methods: {
            get_workers_data() {
                this.$http.get('/admin/app-server-monitor')
                    .then(resp => {
                        //console.log(resp.data);
                        this.workers = resp.data.workers
                        this.Server = resp.data.server
                        if (!this.worker_actions.length) {
                            this.worker_actions = resp.data.worker.supported_actions
                        }
                        if (!this.server_actions.length) {
                            this.server_actions = resp.data.server.supported_actions
                            console.log(this.server_actions)
                        }

                    })
                    .catch(err => {

                    })
                    .finally();
            },
            handle_select_all() {
                this.selected_workers = this.Server.all_workers_ids
            },
            handle_unselect_all() {
                this.selected_workers = []
            },
            handle_worker_action(chosen_action) {
                this.ActionData = this.worker_actions[chosen_action]
                this.is_server_action = true
                this.$bvModal.show('action-modal')
            },
            handle_server_action(chosen_action) {
                this.ActionData = this.server_actions[chosen_action]
                this.is_server_action = true
                this.$bvModal.show('action-modal')
            },

        },
        mounted() {
            this.get_workers_data()

        },
        created() {
            //this.timer = window.setInterval(this.get_workers_data, 1000)

        }
    }
</script>

<style scoped>
    .server
    {
        border: 1px #dfdfdf solid;
        border-radius: 2px;
        margin: 5px;
    }
    .workers
    {
        border: 1px #dfdfdf solid;
        border-radius: 2px;
        margin: 5px;
    }
</style>