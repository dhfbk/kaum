import axios from "axios";

export default axios.create({
    baseURL: 'http://dh-hetzner.fbk.eu:8001/api/',
    timeout: 300000,
});
