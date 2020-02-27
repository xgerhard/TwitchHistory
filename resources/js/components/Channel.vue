<template>
    <div v-if="loading" class="text-center">
        <b-spinner class="m-5" label="Busy"></b-spinner>
    </div>

    <div v-else-if="channel">
        <h2>{{ channel.name }}</h2>
        <div role="tablist" id="streams-list" v-if="channel.twitch_streams[0]">
            <b-card no-body v-for="(twitch_stream, twitch_stream_index) in channel.twitch_streams" v-bind:key="twitch_stream_index">
                <b-card-header header-tag="header" class="p-1" role="tab">
                    <b-button block href="#" v-b-toggle="'accordion-' + twitch_stream_index" variant="dark">
                        <span class="collapse-stream-title">{{ twitch_stream.title }}</span>
                        <b-button size="sm" disabled pill variant="danger" v-if="twitch_stream.duration == 0">🔴LIVE</b-button>
                    </b-button>
                </b-card-header>
                <b-collapse :id="`accordion-${twitch_stream_index}`" visible accordion="my-accordion" role="tabpanel">
                    <b-card-body>
                        <b-card-text>
                            <p>
                                <span><strong>Title:</strong> {{ twitch_stream.title }}</span><br/>
                                <span><strong>Date:</strong> {{ getLocalDateString(twitch_stream.created_at) }}</span><br/>
                                <span><strong>Stream duration:</strong> {{ getDurationString((twitch_stream.duration == 0 ? getDuration(moment.utc(), twitch_stream.created_at) : twitch_stream.duration), true) }}</span>
                            </p>
                            <p>
                                <strong>Stream chapters:</strong>
                                <ul class="list-unstyled">
                                    <b-media tag="li" v-for="(twitch_stream_chapter, twitch_stream_chapter_index) in twitch_stream.twitch_stream_chapters" v-bind:key="twitch_stream_chapter_index">
                                        <template v-slot:aside>
                                            <b-img blank-color="#abc" width="64" v-bind:alt="twitch_stream_chapter.twitch_game.name" v-bind:src="getImgUrl(twitch_stream_chapter.twitch_game.box_art_url)"></b-img>
                                        </template>
                                        <h6 class="mt-0 mb-1" v-if="twitch_stream.vod_id">
                                            <a :href="getVodUrl(
                                                twitch_stream.vod_id,
                                                (twitch_stream_chapter_index == 0 ? false : twitch_stream_chapter.created_at),
                                                twitch_stream.created_at
                                            )" target="blank">{{ twitch_stream_chapter.twitch_game.name }}</a>
                                        </h6>
                                        <h6 class="mt-0 mb-1" v-else>{{ twitch_stream_chapter.twitch_game.name }}</h6>
                                        <p class="mb-0">
                                            {{ getDurationString((twitch_stream_chapter.duration == 0 ? getDuration(moment.utc(), twitch_stream_chapter.created_at) : twitch_stream_chapter.duration), true) }}
                                            <b-button size="sm" disabled variant="outline-primary" v-if="twitch_stream_chapter.duration == 0">Now playing</b-button>
                                        </p>
                                    </b-media>
                                </ul>
                            </p>
                        </b-card-text>
                    </b-card-body>
                </b-collapse>
            </b-card>
        </div>
        <span v-else>No streams found. Start streaming and come back later!</span>
    </div>
</template>

<style>
.media-body {
    margin-top: 10px;
}

#streams-list header button {
    position: absolute;
    right: 5px;
    top: 7px;
    opacity: 1;
}

.collapse-stream-title {
    white-space: nowrap;
}
</style>

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

                // Update page title
                document.title = document.title.replace('Channel', this.channel.name);
            })
            .catch(error => console.log(error))
    },
    methods: {
        getLocalDateString(date) {
            return this.moment.utc(date).local().format('DD MMM HH:mm')
        },
        getDurationString(time, display) {
            var hours = Math.floor(time / 3600),
                minutes = Math.floor((time - (hours * 3600)) / 60),
                seconds = time - (hours * 3600) - (minutes * 60);

            var a = [];
            if (hours >= 1)
                a.push(hours + 'h');

            a.push((minutes == 0 ? '00' : (minutes < 10 ? '0' + minutes : minutes)) + 'm');

            if (seconds >= 1)
                a.push((seconds < 10 ? '0' + seconds : seconds) + 's');

            return a.join(display ? ' ' : '');
        },
        getImgUrl(url) {
            return url.replace('{width}', 64).replace('{height}', 85)
        },
        getVodUrl(vod_id, chapter_created_at, stream_created_at) {
            var url = 'https://www.twitch.tv/videos/' + vod_id;
            if (chapter_created_at) {
                var duration = 
                url = url + '?t=' + this.getDurationString(this.getDuration(chapter_created_at, stream_created_at), false);
            }
            return url;
        },
        getDuration(from, to) {
            return this.moment.utc(from).diff(this.moment.utc(to), 'seconds');
        }
    }
}
</script>