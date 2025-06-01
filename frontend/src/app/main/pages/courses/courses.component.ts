import { Component } from '@angular/core';
import {PageComponent} from '../../../page/page.component';

@Component({
  selector: 'courses',
  imports: [
    PageComponent
  ],
  standalone: true,
  templateUrl: './courses.component.html',
  styleUrl: './courses.component.css'
})
export class CoursesComponent {

}
