import {Component, OnInit} from '@angular/core';
import { OverlayBadgeModule } from 'primeng/overlaybadge';
import { SidebarItemComponent } from '../sidebar-item/sidebar-item.component';
import {CategoryStatsItem, TagCloudItem} from '../../../../types/requests';
import {map} from 'rxjs';
import {TagsService} from '../../../services/tags/tags.service';

import {CategoriesService} from '../../../services/categories/categories.service';
import {Badge} from 'primeng/badge';
import {RouterLink} from '@angular/router';

@Component({
  selector: 'app-miniposts',
  imports: [
    SidebarItemComponent,
    OverlayBadgeModule,
    Badge,
    RouterLink
  ],
  standalone: true,
  templateUrl: './miniposts.component.html',
  styleUrl: './miniposts.component.css'
})
export class MinipostsComponent implements OnInit {
  tagCloudItems: TagCloudItem[] = []
  categoryStatsItems: CategoryStatsItem[] = []
  mode: 'linear' | 'logarithmic' = 'logarithmic'

  constructor(
    private tagsService: TagsService,
    private categoryService: CategoriesService
  ) {
  }

  ngOnInit() {
    this.loadTagCloud(this.mode)
    this.loadCategoryList()
  }

  loadCategoryList(): void {
    this.categoryService.getCategoriesStats().pipe(
      map(res => res.data.items)
    ).subscribe(items => {
      this.categoryStatsItems = items;
    });
  }

  loadTagCloud(mode: string) {
      this.tagsService.getTagCloud().pipe(
        map(res => res.data.items),
        map(items => {
          if (!items || items.length === 0) {
            return [];
          }
          /* Linear Scaling
             - Find the Range: First, you need to find the minimum and maximum total_count in the entire list of tags.
               Let's call them minCount and maxCount
             - Normalize to a 0-1 Range: For any given tag's count, you can calculate its position within that range
               as a value between 0.0 and 1.0
             - Scale to the Target Range (3-7): Now, scale that 0-1 value to your desired range (which has a spread of
               5 values, from 3 to 7)
             - Shift and Round: Finally, shift the range to start at 1 and round the result to the nearest whole number
               to get your final data-weight
           */

          const counts = items.map(item => item.total_count);
          const minCount = Math.min(...counts);
          const maxCount = Math.max(...counts);

          if (mode === 'linear') {
            // Handle the edge case where all tags have the same count
            if (minCount === maxCount) {
              return items.map(item => ({
                ...item,
                weight: 5 // Assign a medium weight if all are equal
              }));
            }

            // 2. Map items to the new structure with the calculated weight
            return items.map(item => {
              // Normalize the count to a 0-8 range
              const scaledValue = ((item.total_count - minCount) / (maxCount - minCount)) * 4;

              // Shift by 1 and round to get an integer between 3 and 7
              const weight = Math.round(scaledValue) + 3;

              return {
                name: item.name,
                slug: item.slug,
                total_count: item.total_count,
                weight: weight
              };
            });
          } else {
            /* Logarithmic scaling
               For tag clouds, data is often skewed (a few tags are extremely popular, and many are rare). Linear
               scaling might result in most tags getting a low weight. A logarithmic scale can provide a better visual
               distribution.
             */

            const logMin = Math.log(minCount + 1);
            const logMax = Math.log(maxCount + 1);

            // If all counts are the same, assign the middle weight 5.
            if (logMin === logMax) {
              return items.map(item => ({ ...item, weight: 5 }));
            }

            return items.map(item => {
              const logCount = Math.log(item.total_count + 1);

              // Scale the normalized log value to the new range width (7 - 3 = 4)
              const scaledValue = ((logCount - logMin) / (logMax - logMin)) * 4;

              // Shift by the new minimum (3) and round
              const weight = Math.round(scaledValue) + 3;

              return {
                name: item.name,
                slug: item.slug,
                total_count: item.total_count,
                weight: weight
              };
            });
          }
        })
      ).subscribe(items => {
        this.tagCloudItems = items;
      });
  }
}
