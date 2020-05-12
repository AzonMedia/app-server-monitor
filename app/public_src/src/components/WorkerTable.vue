<template>
    <div class="table">
        <b-table-simple>
            <b-tbody>
                <b-tr v-for="(row_data, row_index) in TableData" v-bind:key="row_index">

                    <template v-if="row_index==='phpinfo'">
                        <td>
                            <pre>{{row_data}}</pre>
                        </td>
                    </template>
                    <template v-else>
                        <b-td>{{row_index}}</b-td>
                        <b-td>
                            <template v-if="typeof row_data === 'object' || typeof row_data === 'array'">
                                <!-- recursion -->
                                <WorkerTable v-bind:TableData="row_data"></WorkerTable>
                            </template>
                            <template v-else>
                                {{row_data}}
                            </template>
                        </b-td>
                    </template>

                </b-tr>
            </b-tbody>
        </b-table-simple>
    </div>
</template>

<script>
    //this is a recursive template
    export default {
        name: "WorkerTable",
        props: ['TableData'],

    }
</script>

<style scoped>
pre {
    width: 1200px; /* TODO fix this */
}
</style>