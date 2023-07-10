import axios from "axios";
import Cookies from "js-cookie";
import { env, routerUrl } from "../constant";

class CommonMiddleware {
    static baseUrl = `${env.API_URL}`;
    static rewardUrl = `${this.baseUrl}`;

    static setHeader() {
        axios.defaults.headers.common["Accept"] = "application/json";
        axios.defaults.headers.common["Content-Type"] = "application/json";
    }

    static patientCallAxios = async (method, url, params = null) => {
        this.setHeader();
        let result;
        await axios[method](url, params)
            .then((res) => {
                result = res.data;
            })
            .catch((error) => {
                let response = error?.response;
                if (response?.status && response?.status === 500) {
                    result = response;
                    window.location.href =
                        window.location.origin + routerUrl.errorAdmin;
                } else if (response?.status && response?.status === 401) {
                    result = response;
                } else {
                    result = response?.data?.message;
                }
            });
        return result;
    };

    static getZones = async () => {
        return await this.patientCallAxios(
            "get",
            `${this.rewardUrl}/provinces`
        );
    };
}

export default CommonMiddleware;
