<template>
    <div class="table">
        <b-table-simple>
            <b-tbody>
                <b-tr v-for="(row_data, row_index) in TableData">
                    <b-td>{{row_index}}</b-td>
                    <b-td>
                        <template v-if="typeof row_data === 'object' || typeof row_data === 'array'">
                            <WorkerTable v-bind:TableData="row_data"></WorkerTable>
                        </template>
                        <template v-else>
                            {{row_data}}
                        </template>
                    </b-td>
                </b-tr>
            </b-tbody>
        </b-table-simple>
    </div>
</template>

<script>

    export default {
        name: "WorkerTable",
        // props: {
        //     TableData: Object,
        // },
        props: ['TableData'],

    }
</script>

<style scoped>

</style>