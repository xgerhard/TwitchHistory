<template>
    <div v-if="loading" class="text-center">
        <b-spinner class="m-5" label="Busy"></b-spinner>
    </div>

    <div v-else>
        <h2>Streamers</h2>
        <ul v-if="channels">
            <li v-for="channel in channels" v-bind:key="channel.id">
                <router-link :to="{path: '/channel/' + channel.id}">
                    {{ channel.name }}
                    <b-button size="sm" disabled pill variant="danger" v-if="channel.is_live">🔴LIVE</b-button>
                </router-link>
            </li>
        </ul>
    </div>
    
</template>

<script>
export default {
    data () {
        return {
            channels: null,
            loading: true
        }
    },
    mounted () {
        axios.get('http://localhost:8080/api/channel/')
            .then(response => {
                this.channels = response.data;
                this.loading = false;
            })
            .catch(error => console.log(error))
    }
}
</script>