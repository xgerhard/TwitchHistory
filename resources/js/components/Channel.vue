<template>
    <div>
        <div v-if="channel">
            <h2>Channel: {{ channel.name }}</h2>
            <ul>
                <li v-for="twitch_stream in channel.twitch_streams" v-bind:key="twitch_stream.id">
                    <span class="title">
                        [{{ toLocal(twitch_stream.created_at) }}] {{ twitch_stream.title }}
                        <span class="is-live" v-if="twitch_stream.duration == 0">[🔴LIVE]</span>
                    </span>
                    <ul>
                        <li v-for="twitch_stream_chapter in twitch_stream.twitch_stream_chapters" v-bind:key="twitch_stream_chapter.id">
                            {{ twitch_stream_chapter.twitch_game.name }}
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
export default {
    data () {
        return {
            channel: null
        }
    },
    mounted () {
        axios
            .get('http://localhost:8080/api/channel/' + this.$route.params.id)
            .then(response => (this.channel = response.data))
            .catch(error => console.log(error))
    },
    methods: {
        toLocal(date) {
            return this.moment.utc(date).local().format('DD MMM HH:mm')
        }
    }
}
</script>