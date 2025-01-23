import { Component } from '@angular/core';
import {BannerComponent} from '../../banner/banner.component';
import {ActivitiesComponent} from '../../activities/activities.component';
import {LatestNews} from '../../articles/latest-news.component';

@Component({
  selector: 'app-home',
  imports: [
    BannerComponent,
    ActivitiesComponent,
    LatestNews
  ],
  standalone: true,
  templateUrl: './home.component.html',
  styleUrl: './home.component.css'
})
export class HomeComponent {

}
