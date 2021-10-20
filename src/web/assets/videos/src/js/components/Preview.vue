<template>
    <div v-if="previewVideo && !previewError" class="preview flex flex-nowrap items-start">
        <div class="flex-shrink-0">
            <thumb class="pt-0 w-44" :url="previewVideo.thumbnailSourceUrl" :duration="previewVideo.durationNumeric" @playVideo="$emit('playVideo', previewVideo)"></thumb>
        </div>
        <div class="ml-2 flex-shrink max-w-sm min-w-0">
            <div class="line-clamp-2"><strong>{{previewVideo.title}}</strong></div>

            <ul>
                <li class="truncate block">
                    <a :href="previewVideo.url">{{previewVideo.url}}</a>
                </li>
                <li class="truncate block">
                    <a h:ref="previewVideo.author.url" class="light">{{ previewVideo.author.name }}</a>
                </li>
                <li class="truncate block">
                    {{ t('videos', '{plays} plays', { plays: previewVideo.statistic.playCount }) }}
                </li>
                <li class="truncate block">
                    <a @click.prevent="$emit('removeVideo')">{{ t('videos', "Remove") }}</a>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
    import Thumb from './Thumb'

    export default {
        components: {
            Thumb
        },
        props: {
            previewVideo: {
                type: Object,
                default: null
            },
            previewError: {
                type: String,
                default: ''
            }
        },
    }
</script>

