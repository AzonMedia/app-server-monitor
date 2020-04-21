<template>
    <div>
        <div>Application server monitor</div>
        <b-card-group deck>
            <!--  v-bind:selected_workers="selected_workers" -->
            <WorkerC v-for="(WorkerData, index) in workers" v-bind:WorkerData="WorkerData" v-bind:key="WorkerData.worker_id" />
        </b-card-group>
    </div>
</template>

<script>
    import WorkerC from '@GuzabaPlatform.AppServer.Monitor/components/Worker.vue'

    export default {
        name: "AppServerMonitor",
        components: {
            WorkerC,
        },
        data() {
           return {
               timer: 0,
               selected_workers: [],
               workers: [],
               General: {},
           }
        },
        methods: {
            get_workers_data() {
                this.$http.get('/admin/app-server-monitor')
                    .then(resp => {
                        console.log(resp.data);
                        this.workers = resp.data.workers
                        this.General = resp.data.general
                    })
                    .catch(err => {

                    })
                    .finally();
            }
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

</style>