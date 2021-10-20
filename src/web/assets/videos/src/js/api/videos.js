/* global Craft */

import axios from 'axios'

export default {
    getGateways() {
        return axios.get(Craft.getActionUrl('videos/explorer/get-gateways'), {
            headers: {
                'X-CSRF-Token':  Craft.csrfTokenValue,
            }
        })
    },

    getVideos(gateway, method, options) {
        const data = {
            gateway,
            method,
        }

        if (options) {
            data.options = options
        }

        return axios.post(Craft.getActionUrl('videos/explorer/get-videos'), data, {
            headers: {
                'X-CSRF-Token':  Craft.csrfTokenValue,
            }
        })
    },

    getVideo(url) {
        const data = {
            url
        }

        return axios.post(Craft.getActionUrl('videos/explorer/get-video'), data, {
            headers: {
                'X-CSRF-Token':  Craft.csrfTokenValue,
            }
        })
    }
}
