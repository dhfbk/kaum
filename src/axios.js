import axios from "axios";

export default axios.create({
    // baseURL: "http://dh-rhodes.fbk.eu:8001/api/",
    baseURL: process.env.VUE_APP_AXIOS_URL,
    timeout: 300000,
});
