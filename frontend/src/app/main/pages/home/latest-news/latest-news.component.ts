import { Component } from '@angular/core';
import { PageComponent } from '../../../../page/page.component';

@Component({
  selector: 'latest-news',
  imports: [PageComponent],
  standalone: true,
  templateUrl: './latest-news.component.html',
  styleUrl: './latest-news.component.css'
})
export class LatestNews {

}
