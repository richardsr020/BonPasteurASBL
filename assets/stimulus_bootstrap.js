import { startStimulusApp } from '@symfony/stimulus-bundle';

import GedUploadController from './controllers/ged_upload_controller.js';
import GedSearchController from './controllers/ged_search_controller.js';
import ModalController from './controllers/modal_controller.js';
import ConfirmController from './controllers/confirm_controller.js';
import HomeCarouselController from './controllers/home_carousel_controller.js';
import SiteNavController from './controllers/site_nav_controller.js';
import FiguresController from './controllers/figures_controller.js';
import SectionRevealController from './controllers/section_reveal_controller.js';

const app = startStimulusApp();
app.register('ged-upload', GedUploadController);
app.register('ged-search', GedSearchController);
app.register('modal', ModalController);
app.register('confirm', ConfirmController);
app.register('home-carousel', HomeCarouselController);
app.register('site-nav', SiteNavController);
app.register('figures', FiguresController);
app.register('section-reveal', SectionRevealController);
