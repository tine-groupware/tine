/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import {Map, View} from 'ol';
import TileLayer from 'ol/layer/Tile';
import VectorLayer from 'ol/layer/Vector';
import XYZ from 'ol/source/XYZ';
import VectorSource from "ol/source/Vector";
import {fromLonLat} from "ol/proj";
import {Style, Icon} from 'ol/style';
import Feature from 'ol/Feature';
import Point from 'ol/geom/Point';

// NOTE: Chrome & FF require width and height to be set directly in svg code
const flag = `<?xml version="1.0" encoding="utf-8"?>
<svg version="1.1" id="Ebene_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="40px" height="40px" viewBox="0 0 42.5 42.5" style="enable-background:new 0 0 42.5 42.5;" xml:space="preserve">
  <path d="M9.7,9.4c0,0.4-0.1,0.8-0.3,1.2c-0.2,0.4-0.5,0.6-0.9,0.8v23.3c0,0.2-0.1,0.3-0.2,0.4s-0.3,0.2-0.4,0.2H6.8 c-0.2,0-0.3-0.1-0.4-0.2s-0.2-0.3-0.2-0.4V11.4c-0.4-0.2-0.6-0.5-0.9-0.8C5.1,10.2,5,9.8,5,9.4c0-0.7,0.2-1.2,0.7-1.7 C6.2,7.2,6.7,7,7.4,7S8.6,7.2,9,7.7C9.5,8.2,9.7,8.7,9.7,9.4z M36.9,10.5v14.1c0,0.5-0.2,0.8-0.6,1.1c-0.1,0.1-0.2,0.1-0.3,0.2 c-2.7,1.4-4.9,2.1-6.8,2.1c-1.1,0-2.1-0.2-2.9-0.6l-0.5-0.3c-0.8-0.4-1.4-0.7-1.8-0.9s-1-0.4-1.7-0.5c-0.7-0.2-1.4-0.3-2.1-0.3 c-1.3,0-2.7,0.3-4.3,0.8s-3,1.2-4.2,1.9c-0.2,0.1-0.4,0.2-0.6,0.2c-0.2,0-0.4,0-0.6-0.1c-0.4-0.2-0.6-0.6-0.6-1V13.4 c0-0.4,0.2-0.8,0.6-1c0.4-0.3,0.9-0.5,1.4-0.8c0.5-0.3,1.2-0.6,2.1-1c0.9-0.4,1.8-0.7,2.8-0.9c1-0.2,2-0.4,2.9-0.4 c1.4,0,2.7,0.2,3.9,0.6s2.5,0.9,3.9,1.6c0.5,0.2,1,0.4,1.6,0.4c1.5,0,3.4-0.7,5.7-2.1c0.3-0.1,0.5-0.3,0.6-0.3 c0.4-0.2,0.8-0.2,1.1,0C36.7,9.8,36.9,10.1,36.9,10.5z"/>
</svg>`;

export default Ext.extend(Ext.Container, {
    /**
     * @cfg {Number}
     */
    zoom: null,
    /**
     * @cfg {Number}
     */
    lon: null,
    /**
     * @cfg {Number}
     */
    lat: null,

    initComponent() {
        this.mapServiceUrl = this.mapServiceUrl || Tine.Tinebase.configManager.get('mapServiceUrl', 'Tinebase');

        this.on('afterrender', this.injectOL, this);
        this.supr().initComponent.call(this);
    },

    async injectOL() {
        if(!this.center && this.lon && this.lat) {
            this.center = fromLonLat(this.lon, this.lat);
            _.defer(() => {this.addFlagLayer(this.lon, this.lat)});
        }

        this.olMap = new Map({
            target: this.el.id,
            layers: [
                new TileLayer({
                    source: new XYZ({
                        url: this.mapServiceUrl.replace(/\/{0,1}$/, '/{z}/{x}/{y}.png')
                    })
                })
            ],
            view: new View({
                center: this.center || fromLonLat([0, 0]),
                zoom: this.zoom || 4
            })
        });

        this.el.select('.ol-rotate').hide()
        this.el.select('.ol-zoom').setStyle({margin: '10px 0px 10px 10px'})
        this.fireEvent('mapAdded', this);
    },

    setCenter(lon, lat) {
        this.center = fromLonLat([lon, lat]);
        this.olMap.getView().setCenter(this.center);
        this.addFlagLayer(lon, lat)
    },

    addFlagLayer(lon, lat) {
        const iconFeature = new Feature({
            geometry: new Point(fromLonLat([lon, lat])),
        });

        const iconStyle = new Style({
            image: new Icon({
                src: 'data:image/svg+xml;utf8,' + flag,
            })
        });
        iconFeature.setStyle(iconStyle);

        const vectorSource = new VectorSource({
            features: [iconFeature],
        });

        const vectorLayer = new VectorLayer({
            source: vectorSource,
        });
        this.olMap.addLayer(vectorLayer);
    },

    setZoom(zoom) {
        this.zoom = zoom;
        this.olMap.getView().setZoom(this.zoom);
    },

    doLayout() {
        this.supr().doLayout.apply(this, arguments);
        this.olMap?.updateSize();
    }
});
