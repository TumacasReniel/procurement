<template>
    <div>
        <l-map ref="mymap" style="width: 100%; height: 200px; border-radius: 10px;" 
            :zoom="zoom"
            :center="center"
            :options="mapOptions"
            @click="setMarker">
            <l-marker v-if="markerLatLng" :lat-lng="markerLatLng"></l-marker>
            <l-tile-layer :url="url" :attribution="attribution" />
        </l-map>
    </div>
</template>
<script>
import { latLng } from "leaflet";
import { LMap, LTileLayer, LMarker, LTooltip, LIcon } from "@vue-leaflet/vue-leaflet";
export default {
    components: { LMap, LTileLayer, LMarker, LIcon, LTooltip },
    data() {
        return {
            zoom: 8,
            center: latLng(11.6078727, 123.3877174),
            url: "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
            attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors',
            mapOptions: {
                zoomSnap: 0.5,
            },
            coordinates: {},
            markerLatLng: null
        };
    },
    methods: {
        view(){
            setTimeout(() => {
                this.$refs.mymap.leafletObject.invalidateSize(); 
            }, 100);
        },
        setMarker(event){
            this.coordinates = {
                lng: parseFloat(event.latlng.lng.toFixed(6)),
                lat: parseFloat(event.latlng.lat.toFixed(6))
            };
            this.$emit('set',this.coordinates);
            this.markerLatLng = this.coordinates;
            this.$refs.mymap.leafletObject.flyTo(this.coordinates, 15, {
                animate: true,
                duration: 1.0
            });
        },
        empty(){
                this.coordinates = {};
                this.markerLatLng = null;
        }
    },
};
</script>
<style>
.leaflet-control-attribution {
    display: none;
}
</style>