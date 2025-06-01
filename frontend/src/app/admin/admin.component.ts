import { Component } from '@angular/core';
import {PageComponent} from '../page/page.component';

@Component({
  selector: 'admin',
  imports: [PageComponent],
  templateUrl: './admin.component.html',
  standalone: true,
  styleUrl: './admin.component.css'
})
export class AdminComponent {

}
