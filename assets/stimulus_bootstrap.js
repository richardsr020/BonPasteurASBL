import { startStimulusApp } from '@symfony/stimulus-bundle';

import GedUploadController from './controllers/ged_upload_controller.js';
import GedSearchController from './controllers/ged_search_controller.js';
import ModalController from './controllers/modal_controller.js';
import ConfirmController from './controllers/confirm_controller.js';
import HomeCarouselController from './controllers/home_carousel_controller.js';

const app = startStimulusApp();
app.register('ged-upload', GedUploadController);
app.register('ged-search', GedSearchController);
app.register('modal', ModalController);
app.register('confirm', ConfirmController);
app.register('home-carousel', HomeCarouselController);
