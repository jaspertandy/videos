<template>
    <div ref="modal" class="videos-player-modal modal">
        <div class="videos-player bg-black h-full">
            <div v-html="embed"></div>
        </div>
    </div>
</template>

<script>
    /* global Garnish */

    export default {
        data() {
            return {
                modal: null,
                embed: null,
            }
        },

        mounted() {
            const video = this.$root.video
            
            this.embed = video.embedHtml

            this.modal = new Garnish.Modal(this.$refs.modal, {
                resizable: false,

                onHide: function() {
                    this.$emit('hide')
                }.bind(this)
            })
        },

        destroyed() {
            this.modal.$shade[0].remove()
            this.$el.remove()
        }
    }
</script>

<style lang="css">
.videos-player iframe {
    @apply absolute w-full h-full;
}
</style>