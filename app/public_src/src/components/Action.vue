<template>
    <b-modal no-close-on-backdrop id="action-modal" :title="action_title" @ok="modal_ok_handler" @cancel="modal_cancel_handler" @show="modal_show_handler">

        <b-form inline>
            <template v-if="!selected_workers.length">
                No workers selected.
            </template>
            <b-form v-else>
                <template v-for="(Argument, index) in ActionData.arguments">
                    <!-- description="Let us know your name." -->
                    <!-- :state="state" -->
                    <!--
                            :invalid-feedback="invalidFeedback"
                            :valid-feedback="validFeedback"
                    -->
                    <b-form-group
                            :label="Argument.text"
                            :label-for="action_arguments[Argument.name]"
                    >
                        <template v-if="typeof Argument.value === 'boolean'">
                            <b-form-checkbox v-model="action_arguments[Argument.name]" :value="true" unchecked-value="false" />
                        </template>
                        <template v-else>
                            <b-form-input v-model="action_arguments[Argument.name]" :id="Argument.name" :placeholder="Argument.value.toString()"></b-form-input>
                        </template>
                    </b-form-group>
                </template>
            </b-form>
        </b-form>
    </b-modal>
</template>

<script>
    //this is a recursive template
    export default {
        name: "WorkerAction",
        props: ['ActionData','selected_workers','is_server_action'],
        data() {
            return {
                action_arguments: []
            }
        },
        computed: {
            action_title: function() {
                if (this.is_server_action) {
                    return this.Action.name
                } else {
                    return this.ActionData.name + ' for workers ' + this.selected_workers.join(',')
                }

            }
        },
        methods: {
            modal_ok_handler() {
                //console.log(this.action_arguments)
                let sendValues = {};
                this.$http({
                    method: ActionData.method,
                    url: ActionData.route,
                    //data: this.$stringify(sendValues)
                    data: sendValues
                })
                    .then(resp => {
                        //self.requestError = '';
                        //self.successfulMessage = resp.data.message;
                        //self.getClassObjects(self.selectedClassName)
                    })
            },
            modal_cancel_handler() {

            },
            modal_show_handler() {
                console.log(this.ActionData)
                for (let el in this.ActionData.arguments) {
                   this.action_arguments[this.ActionData.arguments[el].name] = this.ActionData.arguments[el].value
                }
            }
        },
    }
</script>

<style scoped>

</style>