<template>
    <div>
        <h2>Streamers</h2>
        <ul v-if="channels">
            <li v-for="channel in channels" v-bind:key="channel.id">
                <router-link :to="{path: '/channel/' + channel.id}">{{ channel.name }}</router-link>
            </li>
        </ul>
    </div>
    
</template>

<script>
export default {
    data () {
        return {
            channels: null
        }
    },
    mounted () {
        axios
            .get('http://localhost:8080/api/channel/')
            .then(response => (this.channels = response.data))
            .catch(error => console.log(error))
    }
}
</script>