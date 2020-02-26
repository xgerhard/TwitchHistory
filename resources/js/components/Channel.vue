<template>
    <div v-if="loading" class="text-center">
        <b-spinner class="m-5" label="Busy"></b-spinner>
    </div>

    <div v-else-if="channel">
        <h2>Channel: {{ channel.name }}</h2>
        <ul v-if="channel.twitch_streams[0]">
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
        <span v-else>No streams found. Start streaming and come back later!</span>
    </div>
</template>

<script>
export default {
    data () {
        return {
            channel: null,
            loading: true
        }
    },
    mounted () {
        axios.get('http://localhost:8080/api/channel/' + this.$route.params.id)
            .then(response => {
                this.channel = response.data;
                this.loading = false;
            })
            .catch(error => console.log(error))
    },
    methods: {
        toLocal(date) {
            return this.moment.utc(date).local().format('DD MMM HH:mm')
        }
    }
}
</script>