<template>
    <div>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <router-link to="/" class="navbar-brand">Twistory</router-link>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav mr-auto">
                        <router-link to="/" v-slot="{ href, navigate, isExactActive }">
                            <li class="nav-item" :class="[isExactActive && 'active']">
                                <a class="nav-link" :href="href" @click="navigate">Home</a>
                            </li>
                        </router-link>
                        <router-link to="/about" v-slot="{ href, navigate, isExactActive }">
                            <li class="nav-item" :class="[isExactActive && 'active']">
                                <a class="nav-link" :href="href" @click="navigate">About</a>
                            </li>
                        </router-link>
                    </ul>
                    <form class="form-inline my-2 my-lg-0">
                        <input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search">
                        <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
                    </form>
                </div>
            </div>
        </nav>
    </div>
</template>